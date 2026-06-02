"""
Agent 1 -- Credit Scoring Service (port 8001)

Accepts raw applicant data, runs the full preprocessing pipeline,
and returns a risk score (0-1), risk category, and top-5 feature importances.
"""

import os
import sys
import joblib
import numpy as np
import pandas as pd
from contextlib import asynccontextmanager
from typing import Optional

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from src.features import prepare_inference_input
from src.preprocessing import (
    clean_outliers,
    handle_missing_values,
    encode_features,
    SCALER_PATH,
    NUMERIC_COLS,
)

_BASE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
_MODEL_PATH  = os.path.join(_BASE, "models", "best_model.pkl")
_PROCESSED_DIR = os.path.join(_BASE, "data", "processed")

_model: object = None
_feature_names: list = None


@asynccontextmanager
async def lifespan(app: FastAPI):
    global _model, _feature_names
    if os.path.exists(_MODEL_PATH):
        _model = joblib.load(_MODEL_PATH)
        ref = os.path.join(_PROCESSED_DIR, "X_train.csv")
        if os.path.exists(ref):
            _feature_names = list(pd.read_csv(ref, nrows=1).columns)
        print(f"[agent1] model loaded: {type(_model).__name__}, features: {len(_feature_names or [])}")
    else:
        print(f"[agent1] WARNING: model not found at {_MODEL_PATH}")
    yield


app = FastAPI(title="Credit Scoring Agent", version="1.0.0", lifespan=lifespan)


# ── Schemas ────────────────────────────────────────────────────────────────────

class ApplicantData(BaseModel):
    person_age: float = Field(..., ge=18, le=100, description="Age (years)")
    person_income: float = Field(..., gt=0, description="Annual income (USD)")
    person_home_ownership: str = Field(..., description="RENT / OWN / MORTGAGE / OTHER")
    person_emp_length: Optional[float] = Field(None, ge=0, le=60, description="Employment length (years)")
    loan_intent: str = Field(..., description="PERSONAL / EDUCATION / MEDICAL / VENTURE / HOMEIMPROVEMENT / DEBTCONSOLIDATION")
    loan_amnt: float = Field(..., gt=0, description="Loan amount (USD)")
    loan_int_rate: float = Field(..., ge=5.0, le=24.0, description="Interest rate (%)")
    loan_percent_income: Optional[float] = Field(None, ge=0.0, le=1.0, description="Loan-to-income ratio (auto-computed if omitted)")
    cb_person_default_on_file: str = Field(..., description="Prior default on record: Y / N")
    cb_person_cred_hist_length: float = Field(..., ge=0, description="Credit history length (years)")


class ScoringResult(BaseModel):
    risk_score: float
    risk_category: str
    model_used: str
    feature_importances: dict


# ── Helpers ────────────────────────────────────────────────────────────────────

def _categorize(score: float) -> str:
    if score < 0.2:
        return "LOW"
    if score < 0.4:
        return "MEDIUM"
    if score < 0.7:
        return "HIGH"
    return "VERY_HIGH"


def _align_columns(df: pd.DataFrame, reference_csv: str) -> pd.DataFrame:
    ref = pd.read_csv(reference_csv, nrows=1)
    for col in ref.columns:
        if col not in df.columns:
            df[col] = 0
    return df[ref.columns]


def _top_importances(model, feature_names: list, top_n: int = 5) -> dict:
    if hasattr(model, "feature_importances_"):
        vals = np.asarray(model.feature_importances_)
    elif hasattr(model, "coef_"):
        vals = np.abs(np.asarray(model.coef_)).ravel()
    else:
        return {}
    if len(vals) != len(feature_names):
        return {}
    idx = np.argsort(vals)[::-1][:top_n]
    return {feature_names[i]: round(float(vals[i]), 6) for i in idx}


# ── Endpoints ──────────────────────────────────────────────────────────────────

@app.post("/score", response_model=ScoringResult)
async def score_applicant(data: ApplicantData):
    if _model is None:
        raise HTTPException(status_code=503, detail="Model not loaded. Run scripts/train_model.py first.")

    # 1 — derive loan_grade and loan_percent_income
    raw = data.model_dump()
    enriched = prepare_inference_input(raw)
    enriched["loan_status"] = 0

    # 2 — build single-row DataFrame
    df = pd.DataFrame([enriched])

    # clean_outliers drops rows where person_emp_length is NaN (NaN <= 60 == False).
    # Temporarily replace NaN with 0 so the row survives, then restore NaN
    # so handle_missing_values can impute the grade-based median properly.
    emp_was_nan = pd.isna(df.loc[0, "person_emp_length"])
    if emp_was_nan:
        df.loc[0, "person_emp_length"] = 0.0

    # 3 — clean / impute / encode
    df = clean_outliers(df)
    if len(df) == 0:
        raise HTTPException(status_code=422, detail="Row removed as outlier — check person_age.")

    if emp_was_nan:
        df.loc[0, "person_emp_length"] = np.nan  # restore so imputation uses grade median

    df = handle_missing_values(df)
    df = encode_features(df)

    # 4 — scale
    scaler = joblib.load(SCALER_PATH)
    present = [c for c in NUMERIC_COLS if c in df.columns]
    df[present] = scaler.transform(df[present])

    # 5 — align to training columns
    X = df.drop(columns=["loan_status"])
    ref = os.path.join(_PROCESSED_DIR, "X_train.csv")
    if os.path.exists(ref):
        X = _align_columns(X, ref)

    # 6 — predict
    risk_score = float(_model.predict_proba(X)[0][1])
    importances = _top_importances(_model, _feature_names or list(X.columns))

    return ScoringResult(
        risk_score=round(risk_score, 4),
        risk_category=_categorize(risk_score),
        model_used=type(_model).__name__,
        feature_importances=importances,
    )


@app.get("/health")
async def health():
    return {
        "status": "ok",
        "agent": "credit_scoring",
        "model_loaded": _model is not None,
        "model_type": type(_model).__name__ if _model else None,
    }


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8001)
