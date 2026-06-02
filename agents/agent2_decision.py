"""
Agent 2 -- Decision Engine (port 8002)

Applies business rules on top of the risk score and returns
APPROVED / DECLINED / REVIEW with reasons and conditions.
"""

from typing import List

from fastapi import FastAPI
from pydantic import BaseModel

app = FastAPI(title="Decision Engine Agent", version="1.0.0")


# ── Schemas ────────────────────────────────────────────────────────────────────

class DecisionInput(BaseModel):
    risk_score: float
    risk_category: str
    applicant_data: dict


class DecisionResult(BaseModel):
    decision: str
    confidence: float
    decline_reasons: List[str]
    conditions: List[str]
    max_approved_amount: float


# ── Endpoint ───────────────────────────────────────────────────────────────────

@app.post("/decide", response_model=DecisionResult)
async def make_decision(data: DecisionInput):
    """
    Business rules (evaluated in priority order):

    1. loan_percent_income > 0.5          → DECLINED  (too much of income)
    2. risk_score > 0.6                   → DECLINED  (very high risk)
    3. prior_default == Y AND score > 0.3 → DECLINED  (default history)
    4. score < 0.2 AND lpi < 0.3          → APPROVED  (clear green)
    5. 0.2 <= score <= 0.4 AND grade A-C  → APPROVED with conditions
    6. everything else                    → REVIEW

    max_approved_amount = min(loan_amnt, income * 0.4 / (1 + score))
    """
    score     = data.risk_score
    applicant = data.applicant_data

    loan_amnt          = float(applicant.get("loan_amnt", 0))
    loan_grade         = str(applicant.get("loan_grade", "D")).upper()
    loan_percent_income = float(applicant.get("loan_percent_income") or 1.0)
    person_income      = float(applicant.get("person_income") or 1.0)
    cb_default         = str(applicant.get("cb_person_default_on_file", "N")).upper()

    decline_reasons: List[str] = []
    conditions: List[str]      = []
    decision   = "REVIEW"
    confidence = 0.55

    # Rule 1 — income ratio too high
    if loan_percent_income > 0.5:
        decline_reasons.append(
            f"Rasio pinjaman terhadap pendapatan ({loan_percent_income:.0%}) melebihi batas maksimum 50%"
        )
        decision   = "DECLINED"
        confidence = 0.92

    # Rule 2 — very high risk score
    elif score > 0.6:
        decline_reasons.append(
            f"Profil risiko kredit termasuk kategori {data.risk_category} dan melebihi ambang batas yang dapat diterima"
        )
        decision   = "DECLINED"
        confidence = min(0.85 + (score - 0.6) * 0.375, 0.99)

    # Rule 3 — prior default with elevated risk
    elif cb_default == "Y" and score > 0.3:
        decline_reasons.append(
            "Terdapat riwayat gagal bayar sebelumnya yang dikombinasikan dengan profil risiko saat ini tidak memenuhi syarat"
        )
        decision   = "DECLINED"
        confidence = 0.80

    # Rule 4 — low risk, low income burden → APPROVED
    elif score < 0.2 and loan_percent_income < 0.3:
        decision   = "APPROVED"
        confidence = min(0.90 + (0.2 - score) * 1.25, 0.99)

    # Rule 5 — moderate risk, good grade → APPROVED with conditions
    elif 0.2 <= score <= 0.4 and loan_grade in ("A", "B", "C"):
        decision   = "APPROVED"
        confidence = 0.70
        conditions.append("Verifikasi dokumen pendapatan (slip gaji atau laporan keuangan 3 bulan terakhir)")
        conditions.append("Penandatanganan surat perjanjian kredit sebelum pencairan dana")

    # Rule 6 — needs manual review
    else:
        decision   = "REVIEW"
        confidence = 0.55
        conditions.append("Diperlukan verifikasi manual oleh tim analis kredit")
        conditions.append("Pemeriksaan kelengkapan dokumen identitas dan pendukung pendapatan")

    # Max approved amount
    if decision in ("APPROVED", "REVIEW"):
        max_amount = min(loan_amnt, person_income * 0.4 / (1 + score))
    else:
        max_amount = 0.0

    return DecisionResult(
        decision=decision,
        confidence=round(min(max(confidence, 0.0), 1.0), 4),
        decline_reasons=decline_reasons,
        conditions=conditions,
        max_approved_amount=round(max_amount, 2),
    )


@app.get("/health")
async def health():
    return {"status": "ok", "agent": "decision_engine"}


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8002)
