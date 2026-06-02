"""
Training script — trains a LightGBM classifier on the processed data and saves
the model to models/best_model.pkl.

Run from the project root:
    python scripts/train_model.py
"""

import os
import sys
import joblib
import pandas as pd
from lightgbm import LGBMClassifier
from sklearn.metrics import f1_score, roc_auc_score, classification_report

PROJECT_ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PROCESSED_DIR = os.path.join(PROJECT_ROOT, "data", "processed")
MODELS_DIR = os.path.join(PROJECT_ROOT, "models")

os.makedirs(MODELS_DIR, exist_ok=True)


def train():
    print("=" * 60)
    print("CREDIT RISK — MODEL TRAINING")
    print("=" * 60)

    print("\n[1/4] Loading processed data...")
    X_train = pd.read_csv(os.path.join(PROCESSED_DIR, "X_train.csv"))
    X_test  = pd.read_csv(os.path.join(PROCESSED_DIR, "X_test.csv"))
    y_train = pd.read_csv(os.path.join(PROCESSED_DIR, "y_train.csv")).squeeze("columns")
    y_test  = pd.read_csv(os.path.join(PROCESSED_DIR, "y_test.csv")).squeeze("columns")
    print(f"  X_train: {X_train.shape}  X_test: {X_test.shape}")
    print(f"  y_train dist: {y_train.value_counts().to_dict()}")

    print("\n[2/4] Training LightGBM model...")
    model = LGBMClassifier(
        n_estimators=300,
        learning_rate=0.05,
        num_leaves=63,
        subsample=0.8,
        colsample_bytree=0.8,
        min_child_samples=20,
        reg_lambda=1.0,
        random_state=42,
        n_jobs=-1,
        verbose=-1,
    )
    model.fit(X_train, y_train)

    print("\n[3/4] Evaluating on test set...")
    y_pred  = model.predict(X_test)
    y_proba = model.predict_proba(X_test)[:, 1]
    f1  = f1_score(y_test, y_pred)
    auc = roc_auc_score(y_test, y_proba)
    print(f"  F1-score : {f1:.4f}")
    print(f"  AUC-ROC  : {auc:.4f}")
    print(classification_report(y_test, y_pred, target_names=["Good", "Default"]))

    print("\n[4/4] Saving model artefacts...")
    model_path = os.path.join(MODELS_DIR, "best_model.pkl")
    meta_path  = os.path.join(MODELS_DIR, "best_model_metadata.pkl")

    joblib.dump(model, model_path)
    joblib.dump(
        {
            "best_model_name": "LightGBM",
            "input_type": "processed_encoded",
            "feature_names": list(X_train.columns),
            "metrics": {"F1": f1, "AUC-ROC": auc},
        },
        meta_path,
    )

    print(f"  Model    : {model_path}")
    print(f"  Metadata : {meta_path}")
    print("\nTraining complete.")
    return model


if __name__ == "__main__":
    train()
