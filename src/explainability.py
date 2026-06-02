"""
SHAP explainability utilities for the credit risk project.

The helper functions are designed for sklearn, XGBoost, LightGBM, RandomForest,
DecisionTree, LogisticRegression, SVM, and CatBoost models.
"""

from __future__ import annotations

from typing import Any

import numpy as np
import pandas as pd
import shap


def _as_dataframe(data: Any, feature_names: list[str] | None = None) -> pd.DataFrame:
    if isinstance(data, pd.DataFrame):
        df = data.copy()
    elif isinstance(data, pd.Series):
        df = data.to_frame().T
    elif isinstance(data, dict):
        df = pd.DataFrame([data])
    else:
        df = pd.DataFrame(data, columns=feature_names)

    if feature_names is not None:
        missing = [col for col in feature_names if col not in df.columns]
        if missing:
            raise ValueError(f"Missing feature columns: {missing}")
        df = df[feature_names]

    return df.reset_index(drop=True)


def _extract_positive_class_shap(raw_values: Any) -> np.ndarray:
    if hasattr(raw_values, "values"):
        raw_values = raw_values.values

    if isinstance(raw_values, list):
        raw_values = raw_values[1] if len(raw_values) > 1 else raw_values[0]

    values = np.asarray(raw_values)

    if values.ndim == 3:
        if values.shape[-1] == 2:
            values = values[:, :, 1]
        elif values.shape[1] == 2:
            values = values[:, 1, :]
        else:
            values = np.squeeze(values)

    if values.ndim == 1:
        values = values.reshape(1, -1)

    return values


def _is_catboost_model(model: Any) -> bool:
    module_name = model.__class__.__module__.lower()
    class_name = model.__class__.__name__.lower()
    return "catboost" in module_name or "catboost" in class_name


def _calculate_shap_values(model: Any, X: pd.DataFrame) -> np.ndarray:
    if _is_catboost_model(model):
        try:
            from catboost import Pool

            cat_features = []
            if hasattr(model, "get_cat_feature_indices"):
                cat_features = list(model.get_cat_feature_indices())

            pool = Pool(X, cat_features=cat_features)
            catboost_values = model.get_feature_importance(pool, type="ShapValues")
            return np.asarray(catboost_values)[:, :-1]
        except Exception:
            pass

    try:
        explainer = shap.TreeExplainer(model)
        raw_values = explainer.shap_values(X)
        return _extract_positive_class_shap(raw_values)
    except Exception:
        background_size = min(100, len(X))
        background = X.sample(background_size, random_state=42) if len(X) > background_size else X

        def predict_default_probability(values: np.ndarray) -> np.ndarray:
            values_df = pd.DataFrame(values, columns=X.columns)
            if hasattr(model, "predict_proba"):
                probabilities = model.predict_proba(values_df)
                probabilities = np.asarray(probabilities)
                if probabilities.ndim == 2 and probabilities.shape[1] > 1:
                    return probabilities[:, 1]
                return probabilities.ravel()

            if hasattr(model, "decision_function"):
                scores = model.decision_function(values_df)
                return np.asarray(scores).ravel()

            predictions = model.predict(values_df)
            return np.asarray(predictions).ravel()

        explainer = shap.Explainer(predict_default_probability, background)
        explanation = explainer(X)
        return _extract_positive_class_shap(explanation)


def _direction_label(shap_value: float) -> str:
    if shap_value > 0:
        return "meningkatkan risiko default"
    if shap_value < 0:
        return "menurunkan risiko default"
    return "netral"


def _format_value(value: Any) -> str:
    if isinstance(value, (int, float, np.integer, np.floating)):
        return f"{float(value):.4g}"
    return str(value)


def generate_shap_explanation(
    model: Any,
    X_test: Any,
    feature_names: list[str],
    sample_index: int = 0,
) -> dict[str, Any]:
    """
    Return dict berisi:
    - shap_values: array nilai SHAP untuk class default/loan_status=1
    - top_factors: list of (feature_name, shap_value, direction), top 5
    - summary_text: penjelasan human-readable untuk applicant terpilih
    """
    X_df = _as_dataframe(X_test, feature_names)
    if len(X_df) == 0:
        raise ValueError("X_test tidak boleh kosong.")

    sample_index = min(max(sample_index, 0), len(X_df) - 1)
    shap_values = _calculate_shap_values(model, X_df)

    if shap_values.shape[1] != len(feature_names):
        raise ValueError(
            f"Jumlah fitur SHAP ({shap_values.shape[1]}) tidak sama dengan "
            f"jumlah feature_names ({len(feature_names)})."
        )

    row_shap = shap_values[sample_index]
    row_data = X_df.iloc[sample_index]
    top_indices = np.argsort(np.abs(row_shap))[::-1][:5]

    top_factors = []
    summary_parts = []

    for idx in top_indices:
        feature = feature_names[idx]
        value = float(row_shap[idx])
        direction = _direction_label(value)
        top_factors.append((feature, value, direction))
        summary_parts.append(
            f"{feature}={_format_value(row_data[feature])} ({direction}, SHAP {value:.4f})"
        )

    summary_text = "Faktor terbesar keputusan model: " + "; ".join(summary_parts)

    return {
        "shap_values": shap_values,
        "top_factors": top_factors,
        "summary_text": summary_text,
    }


def get_decline_reasons(
    applicant_data: Any,
    shap_values: Any,
    threshold: float = 0.3,
) -> list[str]:
    """
    Return list alasan spesifik penolakan berdasarkan SHAP.
    Hanya fitur dengan |shap| > threshold yang dianggap signifikan.
    Untuk alasan penolakan, fitur yang dipakai adalah kontribusi SHAP positif
    karena kontribusi positif mendorong prediksi ke class default/loan_status=1.
    """
    applicant_df = _as_dataframe(applicant_data)
    applicant = applicant_df.iloc[0].to_dict()

    if isinstance(shap_values, dict) and "shap_values" in shap_values:
        shap_values = shap_values["shap_values"]

    values = np.asarray(shap_values)
    if values.ndim == 2:
        values = values[0]
    elif values.ndim == 3:
        values = _extract_positive_class_shap(values)[0]

    feature_names = list(applicant.keys())
    if len(values) != len(feature_names):
        raise ValueError(
            f"Jumlah nilai SHAP ({len(values)}) tidak sama dengan jumlah fitur applicant ({len(feature_names)})."
        )

    reasons = []
    sorted_indices = np.argsort(np.abs(values))[::-1]

    for idx in sorted_indices:
        shap_value = float(values[idx])
        if abs(shap_value) <= threshold or shap_value <= 0:
            continue

        feature = feature_names[idx]
        value = applicant[feature]
        reasons.append(
            f"{feature} bernilai {_format_value(value)} sehingga {_direction_label(shap_value)} "
            f"(SHAP {shap_value:.4f})."
        )

    if not reasons:
        reasons.append(
            f"Tidak ada faktor risiko dengan kontribusi SHAP positif di atas threshold {threshold}."
        )

    return reasons
