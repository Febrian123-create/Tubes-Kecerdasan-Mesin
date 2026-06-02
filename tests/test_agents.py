"""
Test suite for the 3-agent credit risk architecture.

Covers:
  - Agent 1 (Credit Scoring)   — health, low-risk score, high-risk score
  - Agent 2 (Decision Engine)  — approve, decline, income-ratio rule,
                                  prior-default rule, review case, invalid input
  - Agent 3 (LLM Insight)      — approved, declined, review (all via fallback)
  - Edge cases                 — missing fields, boundary values
  - End-to-end pipeline        — orchestrator with mocked agent calls
"""

import numpy as np
import pytest
from unittest.mock import AsyncMock, MagicMock, patch
from httpx import ASGITransport, AsyncClient

# ── Shared test fixtures ───────────────────────────────────────────────────────

LOW_RISK = {
    "person_age": 40,
    "person_income": 120_000,
    "person_home_ownership": "OWN",
    "person_emp_length": 12.0,
    "loan_intent": "EDUCATION",
    "loan_amnt": 5_000,
    "loan_int_rate": 7.5,          # Grade A
    "loan_percent_income": 0.04,
    "cb_person_default_on_file": "N",
    "cb_person_cred_hist_length": 15,
}

HIGH_RISK = {
    "person_age": 22,
    "person_income": 18_000,
    "person_home_ownership": "RENT",
    "person_emp_length": 0.5,
    "loan_intent": "PERSONAL",
    "loan_amnt": 14_000,
    "loan_int_rate": 21.5,         # Grade G
    "loan_percent_income": 0.78,
    "cb_person_default_on_file": "Y",
    "cb_person_cred_hist_length": 1,
}

MEDIUM_RISK = {
    "person_age": 30,
    "person_income": 60_000,
    "person_home_ownership": "RENT",
    "person_emp_length": 5.0,
    "loan_intent": "PERSONAL",
    "loan_amnt": 10_000,
    "loan_int_rate": 11.5,         # Grade B
    "loan_percent_income": 0.17,
    "cb_person_default_on_file": "N",
    "cb_person_cred_hist_length": 4,
}

# Feature names matching X_train.csv column order
_FEAT_NAMES = [
    "person_age", "person_income", "person_emp_length",
    "loan_grade", "loan_amnt", "loan_int_rate", "loan_percent_income",
    "cb_person_default_on_file", "cb_person_cred_hist_length",
    "person_home_ownership_MORTGAGE", "person_home_ownership_OTHER",
    "person_home_ownership_OWN", "person_home_ownership_RENT",
    "loan_intent_DEBTCONSOLIDATION", "loan_intent_EDUCATION",
    "loan_intent_HOMEIMPROVEMENT", "loan_intent_MEDICAL",
    "loan_intent_PERSONAL", "loan_intent_VENTURE",
]


def _make_mock_model(proba: float) -> MagicMock:
    """Return a minimal model mock that returns the given default probability."""
    n = len(_FEAT_NAMES)
    model = MagicMock()
    model.predict_proba.return_value = np.array([[1 - proba, proba]])
    model.feature_importances_ = np.linspace(0.15, 0.01, n)
    model.__class__.__name__ = "LGBMClassifier"
    return model


# ═══════════════════════════════════════════════════════════════════════════════
# AGENT 1 — Credit Scoring
# ═══════════════════════════════════════════════════════════════════════════════

class TestAgent1Health:
    async def test_health_ok(self):
        from agents.agent1_scoring import app
        async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
            r = await c.get("/health")
        assert r.status_code == 200
        assert r.json()["status"] == "ok"
        assert r.json()["agent"] == "credit_scoring"


class TestAgent1Score:
    async def test_low_risk_returns_low_category(self):
        import agents.agent1_scoring as mod
        mock = _make_mock_model(0.10)
        with patch.object(mod, "_model", mock), \
             patch.object(mod, "_feature_names", _FEAT_NAMES):
            from agents.agent1_scoring import app
            async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
                r = await c.post("/score", json=LOW_RISK)

        assert r.status_code == 200
        data = r.json()
        assert data["risk_score"] == pytest.approx(0.10, abs=0.001)
        assert data["risk_category"] == "LOW"
        assert data["model_used"] == "LGBMClassifier"
        assert len(data["feature_importances"]) == 5

    async def test_high_risk_returns_very_high_category(self):
        import agents.agent1_scoring as mod
        mock = _make_mock_model(0.82)
        with patch.object(mod, "_model", mock), \
             patch.object(mod, "_feature_names", _FEAT_NAMES):
            from agents.agent1_scoring import app
            async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
                r = await c.post("/score", json=HIGH_RISK)

        assert r.status_code == 200
        assert r.json()["risk_category"] == "VERY_HIGH"

    async def test_medium_risk_category(self):
        import agents.agent1_scoring as mod
        mock = _make_mock_model(0.30)
        with patch.object(mod, "_model", mock), \
             patch.object(mod, "_feature_names", _FEAT_NAMES):
            from agents.agent1_scoring import app
            async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
                r = await c.post("/score", json=MEDIUM_RISK)

        assert r.status_code == 200
        assert r.json()["risk_category"] == "MEDIUM"

    async def test_model_not_loaded_returns_503(self):
        import agents.agent1_scoring as mod
        with patch.object(mod, "_model", None):
            from agents.agent1_scoring import app
            async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
                r = await c.post("/score", json=LOW_RISK)
        assert r.status_code == 503

    async def test_invalid_age_returns_422(self):
        import agents.agent1_scoring as mod
        bad = {**LOW_RISK, "person_age": 10}  # below ge=18
        from agents.agent1_scoring import app
        async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
            r = await c.post("/score", json=bad)
        assert r.status_code == 422

    async def test_optional_fields_auto_computed(self):
        """loan_percent_income and person_emp_length are optional."""
        import agents.agent1_scoring as mod
        mock = _make_mock_model(0.15)
        data = {k: v for k, v in LOW_RISK.items()
                if k not in ("loan_percent_income", "person_emp_length")}
        with patch.object(mod, "_model", mock), \
             patch.object(mod, "_feature_names", _FEAT_NAMES):
            from agents.agent1_scoring import app
            async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
                r = await c.post("/score", json=data)
        assert r.status_code == 200


# ═══════════════════════════════════════════════════════════════════════════════
# AGENT 2 — Decision Engine
# ═══════════════════════════════════════════════════════════════════════════════

class TestAgent2Health:
    async def test_health_ok(self):
        from agents.agent2_decision import app
        async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
            r = await c.get("/health")
        assert r.status_code == 200
        assert r.json()["status"] == "ok"


class TestAgent2Approve:
    async def test_low_risk_approved(self):
        from agents.agent2_decision import app
        payload = {
            "risk_score": 0.12,
            "risk_category": "LOW",
            "applicant_data": {
                **LOW_RISK,
                "loan_grade": "A",
            },
        }
        async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
            r = await c.post("/decide", json=payload)

        assert r.status_code == 200
        d = r.json()
        assert d["decision"] == "APPROVED"
        assert d["confidence"] > 0.80
        assert d["max_approved_amount"] > 0
        assert d["decline_reasons"] == []

    async def test_moderate_risk_grade_b_approved_with_conditions(self):
        from agents.agent2_decision import app
        payload = {
            "risk_score": 0.28,
            "risk_category": "MEDIUM",
            "applicant_data": {**MEDIUM_RISK, "loan_grade": "B"},
        }
        async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
            r = await c.post("/decide", json=payload)

        d = r.json()
        assert d["decision"] == "APPROVED"
        assert len(d["conditions"]) > 0


class TestAgent2Decline:
    async def test_high_risk_score_declined(self):
        from agents.agent2_decision import app
        payload = {
            "risk_score": 0.75,
            "risk_category": "VERY_HIGH",
            "applicant_data": {**HIGH_RISK, "loan_grade": "G"},
        }
        async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
            r = await c.post("/decide", json=payload)

        d = r.json()
        assert d["decision"] == "DECLINED"
        assert len(d["decline_reasons"]) > 0
        assert d["max_approved_amount"] == 0.0

    async def test_income_ratio_above_50pct_always_declined(self):
        """Rule 1: loan_percent_income > 0.5 → DECLINED regardless of score."""
        from agents.agent2_decision import app
        payload = {
            "risk_score": 0.05,          # technically very safe
            "risk_category": "LOW",
            "applicant_data": {
                "loan_percent_income": 0.65,
                "person_income": 30_000,
                "loan_amnt": 19_500,
                "loan_grade": "A",
                "cb_person_default_on_file": "N",
            },
        }
        async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
            r = await c.post("/decide", json=payload)

        assert r.json()["decision"] == "DECLINED"

    async def test_prior_default_with_elevated_score_declined(self):
        """Rule 3: prior default + score > 0.3 → DECLINED."""
        from agents.agent2_decision import app
        payload = {
            "risk_score": 0.45,
            "risk_category": "HIGH",
            "applicant_data": {
                "loan_percent_income": 0.20,
                "person_income": 50_000,
                "loan_amnt": 10_000,
                "loan_grade": "C",
                "cb_person_default_on_file": "Y",
            },
        }
        async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
            r = await c.post("/decide", json=payload)

        assert r.json()["decision"] == "DECLINED"

    async def test_boundary_score_0_6_exactly(self):
        """risk_score exactly 0.6 is NOT above the > 0.6 threshold."""
        from agents.agent2_decision import app
        payload = {
            "risk_score": 0.60,
            "risk_category": "HIGH",
            "applicant_data": {
                "loan_percent_income": 0.25,
                "person_income": 40_000,
                "loan_amnt": 10_000,
                "loan_grade": "D",
                "cb_person_default_on_file": "N",
            },
        }
        async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
            r = await c.post("/decide", json=payload)

        # 0.6 is not > 0.6, so must not be declined by rule 2
        assert r.json()["decision"] in ("REVIEW", "APPROVED")


class TestAgent2Review:
    async def test_borderline_case_goes_to_review(self):
        from agents.agent2_decision import app
        payload = {
            "risk_score": 0.50,
            "risk_category": "HIGH",
            "applicant_data": {
                "loan_percent_income": 0.25,
                "person_income": 40_000,
                "loan_amnt": 10_000,
                "loan_grade": "D",
                "cb_person_default_on_file": "N",
            },
        }
        async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
            r = await c.post("/decide", json=payload)

        d = r.json()
        assert d["decision"] == "REVIEW"
        assert len(d["conditions"]) > 0
        assert d["max_approved_amount"] > 0


class TestAgent2InvalidInput:
    async def test_missing_required_field(self):
        from agents.agent2_decision import app
        async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
            r = await c.post("/decide", json={"risk_score": 0.3})  # missing risk_category & applicant_data
        assert r.status_code == 422

    async def test_non_numeric_risk_score(self):
        from agents.agent2_decision import app
        async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
            r = await c.post("/decide", json={"risk_score": "bad", "risk_category": "LOW", "applicant_data": {}})
        assert r.status_code == 422


# ═══════════════════════════════════════════════════════════════════════════════
# AGENT 3 — LLM Insight (fallback mode — no API key)
# ═══════════════════════════════════════════════════════════════════════════════

class TestAgent3Health:
    async def test_health_ok(self):
        from agents.agent3_insight import app
        async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
            r = await c.get("/health")
        assert r.status_code == 200
        assert r.json()["status"] == "ok"


_INSIGHT_APPROVED = {
    "decision": "APPROVED",
    "applicant_data": LOW_RISK,
    "decline_reasons": [],
    "risk_score": 0.10,
    "feature_importances": {"loan_int_rate": 0.20, "person_income": 0.18},
}

_INSIGHT_DECLINED = {
    "decision": "DECLINED",
    "applicant_data": HIGH_RISK,
    "decline_reasons": [
        "Profil risiko kredit terlalu tinggi",
        "Rasio pinjaman melebihi batas 50%",
    ],
    "risk_score": 0.82,
    "feature_importances": {"loan_percent_income": 0.30, "loan_int_rate": 0.25},
}

_INSIGHT_REVIEW = {
    "decision": "REVIEW",
    "applicant_data": MEDIUM_RISK,
    "decline_reasons": [],
    "risk_score": 0.45,
    "feature_importances": {"loan_grade": 0.22},
}


class TestAgent3Insight:
    async def test_approved_insight_structure(self):
        from agents.agent3_insight import app
        with patch.dict("os.environ", {"ANTHROPIC_API_KEY": ""}):
            async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
                r = await c.post("/insight", json=_INSIGHT_APPROVED)

        assert r.status_code == 200
        d = r.json()
        assert isinstance(d["summary"], str) and len(d["summary"]) > 10
        assert isinstance(d["improvement_tips"], list) and len(d["improvement_tips"]) >= 3
        assert isinstance(d["estimated_approval_timeline"], str)
        assert isinstance(d["risk_explanation"], str)

    async def test_declined_insight_has_multiple_tips(self):
        from agents.agent3_insight import app
        with patch.dict("os.environ", {"ANTHROPIC_API_KEY": ""}):
            async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
                r = await c.post("/insight", json=_INSIGHT_DECLINED)

        d = r.json()
        assert r.status_code == 200
        assert len(d["improvement_tips"]) >= 3

    async def test_review_insight_mentions_timeline(self):
        from agents.agent3_insight import app
        with patch.dict("os.environ", {"ANTHROPIC_API_KEY": ""}):
            async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
                r = await c.post("/insight", json=_INSIGHT_REVIEW)

        d = r.json()
        assert r.status_code == 200
        assert "hari" in d["estimated_approval_timeline"].lower() or \
               "bulan" in d["estimated_approval_timeline"].lower()

    async def test_insight_missing_fields_returns_422(self):
        from agents.agent3_insight import app
        async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
            r = await c.post("/insight", json={"decision": "APPROVED"})
        assert r.status_code == 422


# ═══════════════════════════════════════════════════════════════════════════════
# END-TO-END — Orchestrator with mocked agents
# ═══════════════════════════════════════════════════════════════════════════════

def _build_httpx_mock(scoring: dict, decision: dict, insight: dict):
    """Return a patched httpx.AsyncClient whose .post() returns agent responses."""

    def _resp(data: dict):
        m = MagicMock()
        m.raise_for_status = MagicMock()
        m.json.return_value = data
        return m

    responses = [_resp(scoring), _resp(decision), _resp(insight)]
    call_count = {"n": 0}

    async def mock_post(url, **kwargs):
        idx = call_count["n"]
        call_count["n"] += 1
        return responses[min(idx, len(responses) - 1)]

    mock_client = MagicMock()
    mock_client.post = mock_post
    mock_client.__aenter__ = AsyncMock(return_value=mock_client)
    mock_client.__aexit__ = AsyncMock(return_value=False)
    return mock_client


class TestEndToEnd:
    async def test_apply_loan_approved(self):
        from api.orchestrator import app

        mock_scoring = {
            "risk_score": 0.12, "risk_category": "LOW",
            "model_used": "LGBMClassifier",
            "feature_importances": {"loan_int_rate": 0.20},
        }
        mock_decision = {
            "decision": "APPROVED", "confidence": 0.92,
            "decline_reasons": [], "conditions": [],
            "max_approved_amount": 4800.0,
        }
        mock_insight = {
            "summary": "Selamat, pinjaman disetujui.",
            "improvement_tips": ["Bayar tepat waktu"],
            "estimated_approval_timeline": "1-3 hari kerja",
            "risk_explanation": "Profil keuangan baik.",
        }

        mock_client = _build_httpx_mock(mock_scoring, mock_decision, mock_insight)
        import api.orchestrator as orch
        with patch.object(orch.httpx, "AsyncClient", return_value=mock_client):
            async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
                r = await c.post("/loan/apply", json=LOW_RISK)

        assert r.status_code == 200
        d = r.json()
        assert "application_id" in d
        assert d["decision"]["decision"] == "APPROVED"
        assert d["scoring"]["risk_score"] == 0.12
        assert "summary" in d["insight"]
        assert "timestamp" in d

    async def test_apply_loan_declined(self):
        from api.orchestrator import app

        mock_scoring = {
            "risk_score": 0.82, "risk_category": "VERY_HIGH",
            "model_used": "LGBMClassifier",
            "feature_importances": {"loan_percent_income": 0.30},
        }
        mock_decision = {
            "decision": "DECLINED", "confidence": 0.93,
            "decline_reasons": ["Profil risiko terlalu tinggi"],
            "conditions": [], "max_approved_amount": 0.0,
        }
        mock_insight = {
            "summary": "Mohon maaf, pinjaman tidak dapat disetujui.",
            "improvement_tips": ["Kurangi utang", "Tingkatkan pendapatan"],
            "estimated_approval_timeline": "6-12 bulan",
            "risk_explanation": "Profil risiko tidak memenuhi syarat.",
        }

        mock_client = _build_httpx_mock(mock_scoring, mock_decision, mock_insight)
        import api.orchestrator as orch
        with patch.object(orch.httpx, "AsyncClient", return_value=mock_client):
            async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
                r = await c.post("/loan/apply", json=HIGH_RISK)

        assert r.status_code == 200
        assert r.json()["decision"]["decision"] == "DECLINED"

    async def test_status_found_after_apply(self):
        """Status endpoint should return the stored result by application_id."""
        from api.orchestrator import app, _applications

        app_id = "TESTID000001"
        _applications[app_id] = {
            "application_id": app_id,
            "scoring": {"risk_score": 0.12, "risk_category": "LOW"},
            "decision": {"decision": "APPROVED", "confidence": 0.92,
                         "decline_reasons": [], "conditions": [], "max_approved_amount": 5000.0},
            "insight": {},
            "timestamp": "2026-06-02T10:00:00",
        }

        async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
            r = await c.get(f"/loan/status/{app_id}")

        assert r.status_code == 200
        assert r.json()["status"] == "APPROVED"

    async def test_status_not_found(self):
        from api.orchestrator import app
        async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
            r = await c.get("/loan/status/NONEXISTENT")
        assert r.status_code == 404

    async def test_orchestrator_health(self):
        from api.orchestrator import app
        async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
            r = await c.get("/health")
        assert r.status_code == 200
        assert r.json()["status"] == "ok"

    async def test_apply_missing_required_field(self):
        """Orchestrator should return 422 for invalid input before calling agents."""
        from api.orchestrator import app
        bad = {k: v for k, v in LOW_RISK.items() if k != "person_income"}
        async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
            r = await c.post("/loan/apply", json=bad)
        assert r.status_code == 422
