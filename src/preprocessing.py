"""
Credit Risk — full preprocessing pipeline.
Run directly: python src/preprocessing.py
"""

import os
import pandas as pd
import numpy as np
import joblib
from sklearn.preprocessing import StandardScaler
from sklearn.model_selection import train_test_split
from imblearn.over_sampling import SMOTE

# ── paths ────────────────────────────────────────────────────────────────────
_BASE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
RAW_PATH = os.path.join(_BASE, "data", "raw", "credit_risk_dataset.csv")
PROCESSED_DIR = os.path.join(_BASE, "data", "processed")
SCALER_PATH = os.path.join(PROCESSED_DIR, "scaler.joblib")

NUMERIC_COLS = [
    "person_age", "person_income", "person_emp_length",
    "loan_amnt", "loan_int_rate", "loan_percent_income",
    "cb_person_cred_hist_length",
]

GRADE_MAP = {"A": 1, "B": 2, "C": 3, "D": 4, "E": 5, "F": 6, "G": 7}
DEFAULT_MAP = {"N": 0, "Y": 1}

OHE_COLS = ["person_home_ownership", "loan_intent"]
TARGET = "loan_status"


# ── cleaning ─────────────────────────────────────────────────────────────────

def clean_outliers(df: pd.DataFrame) -> pd.DataFrame:
    """Remove biologically implausible ages and employment lengths."""
    before = len(df)
    df = df[df["person_age"] <= 100].copy()
    df = df[df["person_emp_length"] <= 60].copy()
    print(f"[clean_outliers] removed {before - len(df)} rows -> {len(df)} remain")
    return df.reset_index(drop=True)


def handle_missing_values(df: pd.DataFrame) -> pd.DataFrame:
    """Fill nulls using within-grade medians to respect risk segmentation."""
    for col in ["person_emp_length", "loan_int_rate"]:
        medians = df.groupby("loan_grade")[col].transform("median")
        # Fall back to global median where a whole grade is null
        global_median = df[col].median()
        df[col] = df[col].fillna(medians).fillna(global_median)
    null_remaining = df.isnull().sum().sum()
    print(f"[handle_missing_values] nulls remaining: {null_remaining}")
    return df


# ── encoding ─────────────────────────────────────────────────────────────────

def encode_features(df: pd.DataFrame) -> pd.DataFrame:
    """Label-encode ordinal cols; one-hot encode nominal cols."""
    df = df.copy()

    # Ordinal
    df["loan_grade"] = df["loan_grade"].map(GRADE_MAP)
    df["cb_person_default_on_file"] = df["cb_person_default_on_file"].map(DEFAULT_MAP)

    # Nominal → one-hot (drop_first avoids perfect multicollinearity)
    df = pd.get_dummies(df, columns=OHE_COLS, drop_first=False, dtype=int)

    print(f"[encode_features] shape after encoding: {df.shape}")
    return df


# ── scaling ──────────────────────────────────────────────────────────────────

def normalize_features(df: pd.DataFrame, scaler: StandardScaler = None):
    """
    Fit-transform (training) or transform-only (inference).
    Returns (df_scaled, fitted_scaler).
    """
    df = df.copy()
    cols_present = [c for c in NUMERIC_COLS if c in df.columns]

    if scaler is None:
        scaler = StandardScaler()
        df[cols_present] = scaler.fit_transform(df[cols_present])
        print(f"[normalize_features] fitted new StandardScaler on {cols_present}")
    else:
        df[cols_present] = scaler.transform(df[cols_present])
        print(f"[normalize_features] applied existing scaler")

    return df, scaler


# ── split & balance ──────────────────────────────────────────────────────────

def split_and_balance(df: pd.DataFrame):
    """
    80/20 stratified split then SMOTE on the training portion only.
    Returns X_train, X_test, y_train, y_test (all as DataFrames/Series).
    """
    X = df.drop(columns=[TARGET])
    y = df[TARGET]

    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.20, random_state=42, stratify=y
    )
    print(f"[split_and_balance] pre-SMOTE  train={len(X_train)}, test={len(X_test)}")
    print(f"  train class dist: {y_train.value_counts().to_dict()}")

    smote = SMOTE(random_state=42)
    X_train_res, y_train_res = smote.fit_resample(X_train, y_train)
    print(f"[split_and_balance] post-SMOTE train={len(X_train_res)}")
    print(f"  train class dist: {pd.Series(y_train_res).value_counts().to_dict()}")

    return (
        pd.DataFrame(X_train_res, columns=X_train.columns),
        X_test.reset_index(drop=True),
        pd.Series(y_train_res, name=TARGET),
        y_test.reset_index(drop=True),
    )


# ── full pipeline ─────────────────────────────────────────────────────────────

def run_full_pipeline(csv_path: str = RAW_PATH):
    """
    Execute all preprocessing steps end-to-end, persist artefacts,
    and return (X_train, X_test, y_train, y_test).
    """
    os.makedirs(PROCESSED_DIR, exist_ok=True)

    print("=" * 60)
    print("CREDIT RISK — PREPROCESSING PIPELINE")
    print("=" * 60)

    # 1. Load
    df = pd.read_csv(csv_path)
    print(f"[load] shape: {df.shape}")

    # 2. Clean
    df = clean_outliers(df)

    # 3. Impute
    df = handle_missing_values(df)

    # 4. Encode
    df = encode_features(df)

    # 5. Scale (fit on full pre-split data, then re-apply after split)
    #    We scale before split to get the scaler, then re-scale properly.
    #    Correct approach: scale only on train → done inside split_and_balance wrapper.
    X = df.drop(columns=[TARGET])
    y = df[TARGET]
    X_train_raw, X_test_raw, y_train, y_test = train_test_split(
        X, y, test_size=0.20, random_state=42, stratify=y
    )

    # Fit scaler on train only
    scaler = StandardScaler()
    cols_present = [c for c in NUMERIC_COLS if c in X_train_raw.columns]
    X_train_scaled = X_train_raw.copy()
    X_train_scaled[cols_present] = scaler.fit_transform(X_train_raw[cols_present])
    X_test_scaled = X_test_raw.copy()
    X_test_scaled[cols_present] = scaler.transform(X_test_raw[cols_present])

    # 6. SMOTE on training only
    smote = SMOTE(random_state=42)
    X_train_res, y_train_res = smote.fit_resample(X_train_scaled, y_train)
    X_train_final = pd.DataFrame(X_train_res, columns=X_train_scaled.columns)
    y_train_final = pd.Series(y_train_res, name=TARGET)

    print(f"\n[split_and_balance] pre-SMOTE  train={len(X_train_raw)}, test={len(X_test_raw)}")
    print(f"  train class dist (pre-SMOTE): {y_train.value_counts().to_dict()}")
    print(f"[split_and_balance] post-SMOTE train={len(X_train_final)}")
    print(f"  train class dist (post-SMOTE): {y_train_final.value_counts().to_dict()}")

    # 7. Persist
    joblib.dump(scaler, SCALER_PATH)
    X_train_final.to_csv(os.path.join(PROCESSED_DIR, "X_train.csv"), index=False)
    X_test_scaled.reset_index(drop=True).to_csv(os.path.join(PROCESSED_DIR, "X_test.csv"), index=False)
    y_train_final.to_csv(os.path.join(PROCESSED_DIR, "y_train.csv"), index=False)
    y_test.reset_index(drop=True).to_csv(os.path.join(PROCESSED_DIR, "y_test.csv"), index=False)

    print("\n[pipeline] Saved artefacts:")
    print(f"  {SCALER_PATH}")
    for fname in ["X_train.csv", "X_test.csv", "y_train.csv", "y_test.csv"]:
        print(f"  {os.path.join(PROCESSED_DIR, fname)}")

    print("\n" + "=" * 60)
    print("PIPELINE SUMMARY")
    print("=" * 60)
    print(f"  X_train shape : {X_train_final.shape}")
    print(f"  X_test  shape : {X_test_scaled.shape}")
    print(f"  y_train dist  : {y_train_final.value_counts().to_dict()}")
    print(f"  y_test  dist  : {y_test.value_counts().to_dict()}")
    print(f"  Features      : {list(X_train_final.columns)}")
    print("=" * 60)

    return (
        X_train_final,
        X_test_scaled.reset_index(drop=True),
        y_train_final,
        y_test.reset_index(drop=True),
    )


if __name__ == "__main__":
    run_full_pipeline()
