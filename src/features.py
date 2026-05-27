"""
Feature engineering utilities — derived features on top of raw columns.
These are applied before encode_features / normalize_features.
"""

import pandas as pd
import numpy as np

# ── loan_grade derivation ─────────────────────────────────────────────────────
# Thresholds derived from Section 10 of 01_eda.ipynb:
# midpoint between adjacent grade medians (A=7.49, B=10.99, C=13.48,
# D=15.31, E=16.82, F=18.54, G=20.16).
_GRADE_THRESHOLDS = [9.24, 12.24, 14.40, 16.07, 17.68, 19.35]
_GRADE_LABELS     = [1,    2,     3,     4,     5,     6,     7]   # A=1 .. G=7


def derive_loan_grade(loan_int_rate: float) -> int:
    """
    Map a loan interest rate (%) to an integer loan_grade (A=1 .. G=7).
    Uses midpoint thresholds between adjacent grade medians from EDA.
    """
    for threshold, grade in zip(_GRADE_THRESHOLDS, _GRADE_LABELS):
        if loan_int_rate < threshold:
            return grade
    return 7  # G


def prepare_inference_input(raw_input: dict) -> dict:
    """
    Prepare a raw applicant dict for model inference:
      - Auto-derive loan_grade from loan_int_rate (never ask the applicant).
      - Auto-calculate loan_percent_income if not provided.
    Returns a new dict ready to pass into the preprocessing pipeline.
    """
    data = dict(raw_input)

    # Derive loan_grade — always overwrite even if caller passed one
    if "loan_int_rate" in data and data["loan_int_rate"] is not None:
        data["loan_grade"] = derive_loan_grade(float(data["loan_int_rate"]))
    else:
        raise ValueError("loan_int_rate is required to derive loan_grade")

    # Calculate loan_percent_income if absent or None
    if data.get("loan_percent_income") is None:
        income = float(data.get("person_income", 0))
        amnt   = float(data.get("loan_amnt", 0))
        data["loan_percent_income"] = round(amnt / income, 4) if income > 0 else 0.0

    return data


# ── domain-informed derived features ─────────────────────────────────────────

def add_derived_features(df: pd.DataFrame) -> pd.DataFrame:
    """
    Create domain-informed features that help separate good/bad loans.
    Safe to call before or after outlier removal.
    """
    df = df.copy()

    # Debt-to-income: loan amount relative to annual income
    df["debt_to_income"] = df["loan_amnt"] / (df["person_income"] + 1)

    # Annual loan cost relative to income (interest pressure)
    if "loan_int_rate" in df.columns:
        df["annual_interest_cost"] = (
            df["loan_amnt"] * df["loan_int_rate"] / 100
        ) / (df["person_income"] + 1)

    # Employment stability bucket (0=short, 1=mid, 2=long)
    df["emp_stability"] = pd.cut(
        df["person_emp_length"].fillna(0),
        bins=[-1, 2, 10, 9999],
        labels=[0, 1, 2],
    ).astype(int)

    # Age group: young (<30), mid (30-55), senior (>55)
    df["age_group"] = pd.cut(
        df["person_age"],
        bins=[0, 29, 55, 9999],
        labels=[0, 1, 2],
    ).astype(int)

    # Credit history relative to age (how long they've had credit vs life span)
    df["cred_hist_ratio"] = df["cb_person_cred_hist_length"] / (df["person_age"] + 1)

    return df


def get_feature_names_after_encoding(df_encoded: pd.DataFrame) -> list[str]:
    """Return feature column names (everything except loan_status)."""
    return [c for c in df_encoded.columns if c != "loan_status"]
