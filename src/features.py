"""
Feature engineering utilities — derived features on top of raw columns.
These are applied before encode_features / normalize_features.
"""

import pandas as pd
import numpy as np


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
