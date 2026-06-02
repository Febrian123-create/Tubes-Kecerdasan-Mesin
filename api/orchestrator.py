"""
Orchestrator API -- Main API Gateway (port 8000)

Coordinates the three agents in sequence:
  1. Agent 1 (port 8001) — compute risk score
  2. Agent 2 (port 8002) — apply business rules, produce decision
  3. Agent 3 (port 8003) — generate LLM insight for the applicant

Provides:
  POST /loan/apply          — full pipeline
  GET  /loan/status/{id}    — retrieve a past application
  GET  /health              — liveness check
"""

import os
import uuid
from datetime import datetime
from typing import Optional

import httpx
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field

AGENT1_URL = os.getenv("AGENT1_URL", "http://localhost:8001")
AGENT2_URL = os.getenv("AGENT2_URL", "http://localhost:8002")
AGENT3_URL = os.getenv("AGENT3_URL", "http://localhost:8003")

app = FastAPI(title="Credit Risk Orchestrator", version="1.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# In-memory store: application_id -> full result dict
_applications: dict = {}


# ── Grade derivation (mirrors src/features.py) ─────────────────────────────────

_GRADE_THRESHOLDS = [9.24, 12.24, 14.40, 16.07, 17.68, 19.35]
_GRADE_LABELS     = ["A",  "B",   "C",   "D",   "E",   "F",  "G"]

def _grade_label(rate: float) -> str:
    for threshold, label in zip(_GRADE_THRESHOLDS, _GRADE_LABELS):
        if rate < threshold:
            return label
    return "G"


# ── Schema ─────────────────────────────────────────────────────────────────────

class ApplicantData(BaseModel):
    person_age: float = Field(..., ge=18, le=100)
    person_income: float = Field(..., gt=0)
    person_home_ownership: str
    person_emp_length: Optional[float] = Field(None, ge=0, le=60)
    loan_intent: str
    loan_amnt: float = Field(..., gt=0)
    loan_int_rate: float = Field(..., ge=5.0, le=24.0)
    loan_percent_income: Optional[float] = Field(None, ge=0.0, le=1.0)
    cb_person_default_on_file: str
    cb_person_cred_hist_length: float = Field(..., ge=0)


# ── Helpers ────────────────────────────────────────────────────────────────────

def _enrich(applicant: ApplicantData) -> dict:
    """Return applicant dict with loan_grade and computed loan_percent_income."""
    d = applicant.model_dump()
    d["loan_grade"] = _grade_label(d["loan_int_rate"])
    if d.get("loan_percent_income") is None:
        d["loan_percent_income"] = (
            round(d["loan_amnt"] / d["person_income"], 4) if d["person_income"] > 0 else 0.0
        )
    return d


# ── Endpoints ──────────────────────────────────────────────────────────────────

@app.post("/loan/apply")
async def apply_loan(applicant: ApplicantData):
    application_id = str(uuid.uuid4())[:12].upper()
    enriched = _enrich(applicant)

    async with httpx.AsyncClient(timeout=30.0) as client:
        # Step 1 — risk scoring
        r1 = await client.post(f"{AGENT1_URL}/score", json=applicant.model_dump())
        r1.raise_for_status()
        scoring = r1.json()

        # Step 2 — decision
        r2 = await client.post(
            f"{AGENT2_URL}/decide",
            json={
                "risk_score":     scoring["risk_score"],
                "risk_category":  scoring["risk_category"],
                "applicant_data": enriched,
            },
        )
        r2.raise_for_status()
        decision = r2.json()

        # Step 3 — insight (always generated)
        r3 = await client.post(
            f"{AGENT3_URL}/insight",
            json={
                "decision":            decision["decision"],
                "applicant_data":      enriched,
                "decline_reasons":     decision["decline_reasons"],
                "risk_score":          scoring["risk_score"],
                "feature_importances": scoring["feature_importances"],
            },
        )
        r3.raise_for_status()
        insight = r3.json()

    result = {
        "application_id": application_id,
        "scoring":         scoring,
        "decision":        decision,
        "insight":         insight,
        "timestamp":       datetime.now().isoformat(),
    }
    _applications[application_id] = result
    return result


@app.get("/loan/status/{application_id}")
async def get_status(application_id: str):
    record = _applications.get(application_id.upper())
    if not record:
        raise HTTPException(
            status_code=404,
            detail=f"Application '{application_id}' not found. It may have expired or never existed.",
        )
    return {
        "application_id": application_id.upper(),
        "status":         record["decision"]["decision"],
        "decision":       record["decision"],
        "scoring":        record["scoring"],
        "timestamp":      record["timestamp"],
    }


@app.get("/health")
async def health():
    return {
        "status":  "ok",
        "service": "orchestrator",
        "agents": {
            "agent1_scoring":  AGENT1_URL,
            "agent2_decision": AGENT2_URL,
            "agent3_insight":  AGENT3_URL,
        },
    }


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
