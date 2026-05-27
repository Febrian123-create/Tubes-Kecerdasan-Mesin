"""
Agent 1 — Loan Scoring Agent.

Receives applicant data (without loan_grade — derived automatically),
runs the preprocessing pipeline, and returns a risk score + decision.

Usage:
    from agents.agent1_scoring import score_applicant, ApplicantData
"""

import os
import sys
import joblib
import numpy as np
import pandas as pd
from pydantic import BaseModel, Field
from typing import Optional

# Allow imports from project root
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from src.features import prepare_inference_input
from src.preprocessing import (
    clean_outliers,
    handle_missing_values,
    encode_features,
    SCALER_PATH,
    NUMERIC_COLS,
)

_PROCESSED_DIR = os.path.join(
    os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
    "data", "processed",
)


# ── Schema ────────────────────────────────────────────────────────────────────

class ApplicantData(BaseModel):
    """
    Input schema for a loan applicant.
    loan_grade is intentionally excluded — it is derived automatically
    from loan_int_rate so the frontend never needs to ask for it.
    """
    person_age: float = Field(..., ge=18, le=100, description="Applicant age (years)")
    person_income: float = Field(..., gt=0, description="Annual income (USD)")
    person_home_ownership: str = Field(..., description="RENT / OWN / MORTGAGE / OTHER")
    person_emp_length: Optional[float] = Field(None, ge=0, le=60, description="Employment length (years)")
    loan_intent: str = Field(..., description="PERSONAL / EDUCATION / MEDICAL / VENTURE / HOMEIMPROVEMENT / DEBTCONSOLIDATION")
    loan_amnt: float = Field(..., gt=0, description="Loan amount requested (USD)")
    loan_int_rate: float = Field(..., ge=5.0, le=24.0, description="Interest rate offered by bank (%)")
    loan_percent_income: Optional[float] = Field(None, ge=0.0, le=1.0, description="Loan-to-income ratio (auto-calculated if omitted)")
    cb_person_default_on_file: str = Field(..., description="Prior default on record: Y / N")
    cb_person_cred_hist_length: float = Field(..., ge=0, description="Credit history length (years)")


# ── Feature alignment ─────────────────────────────────────────────────────────

def _align_columns(df: pd.DataFrame, reference_csv: str) -> pd.DataFrame:
    """
    Align df columns to match training feature order.
    Adds zero-filled columns for any OHE dummy missing in this row.
    """
    ref = pd.read_csv(reference_csv, nrows=1)
    for col in ref.columns:
        if col not in df.columns:
            df[col] = 0
    return df[ref.columns]


# ── Scoring ───────────────────────────────────────────────────────────────────

def score_applicant(applicant: ApplicantData, model=None) -> dict:
    """
    Full inference pipeline for a single applicant.

    Steps:
      1. prepare_inference_input  — auto-derive loan_grade & loan_percent_income
      2. Build single-row DataFrame
      3. clean_outliers / handle_missing_values / encode_features
      4. StandardScaler transform (fitted on training data)
      5. Align columns to training feature order
      6. Model predict_proba (if model provided) or return processed features

    Returns dict with keys: loan_grade_derived, loan_percent_income, features,
    default_probability, decision.
    """
    # Step 1 — derive hidden fields
    raw = applicant.model_dump()
    enriched = prepare_inference_input(raw)

    # Step 2 — single-row DataFrame (add dummy loan_status=0 for pipeline compat)
    enriched["loan_status"] = 0
    df = pd.DataFrame([enriched])

    # Step 3 — clean & encode (outlier removal is a no-op for valid inference input)
    df = clean_outliers(df)
    if len(df) == 0:
        raise ValueError("Input row was removed as an outlier — check person_age and person_emp_length.")
    df = handle_missing_values(df)
    df = encode_features(df)

    # Step 4 — scale numeric columns
    scaler = joblib.load(SCALER_PATH)
    cols_present = [c for c in NUMERIC_COLS if c in df.columns]
    df[cols_present] = scaler.transform(df[cols_present])

    # Step 5 — drop target, align to training columns
    X = df.drop(columns=["loan_status"])
    x_ref = os.path.join(_PROCESSED_DIR, "X_train.csv")
    if os.path.exists(x_ref):
        X = _align_columns(X, x_ref)

    # Step 6 — predict
    result = {
        "loan_grade_derived": enriched["loan_grade"],
        "loan_grade_label": "ABCDEFG"[enriched["loan_grade"] - 1],
        "loan_percent_income": enriched["loan_percent_income"],
        "features": X.to_dict(orient="records")[0],
        "default_probability": None,
        "decision": None,
    }

    if model is not None:
        proba = float(model.predict_proba(X)[0][1])
        result["default_probability"] = round(proba, 4)
        result["decision"] = "DECLINE" if proba >= 0.5 else "APPROVE"

    return result


# ── CLI smoke test ────────────────────────────────────────────────────────────

if __name__ == "__main__":
    sample = ApplicantData(
        person_age=30,
        person_income=60000,
        person_home_ownership="RENT",
        person_emp_length=5.0,
        loan_intent="PERSONAL",
        loan_amnt=10000,
        loan_int_rate=11.5,          # will map to Grade B (2)
        loan_percent_income=None,    # auto-calculated
        cb_person_default_on_file="N",
        cb_person_cred_hist_length=4,
    )

    result = score_applicant(sample)
    print("=== Agent 1 — Scoring Result ===")
    print(f"  Derived loan_grade : {result['loan_grade_label']} ({result['loan_grade_derived']})")
    print(f"  loan_percent_income: {result['loan_percent_income']:.4f}")
    print(f"  Default probability: {result['default_probability']}")
    print(f"  Decision           : {result['decision']}")
    print(f"  Feature vector     : {list(result['features'].keys())}")
