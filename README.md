# Credit Risk Prediction — Agentic AI Application

Binary classification to predict loan default using an agentic AI pipeline.

## Dataset
- 32,581 rows, 12 columns
- Target: `loan_status` (0 = good, 1 = default)
- Class imbalance: ~78% good vs ~22% default

## Project Structure
```
credit_risk_project/
├── data/
│   ├── raw/                  # Original dataset
│   └── processed/            # Preprocessed splits
├── notebooks/
│   └── 01_eda.ipynb          # Exploratory Data Analysis
├── src/
│   ├── preprocessing.py      # Data cleaning & feature engineering pipeline
│   └── features.py           # Feature engineering utilities
├── models/                   # Saved model artifacts
├── agents/                   # Agent implementations
├── api/                      # FastAPI orchestrator
├── frontend/                 # Streamlit / React UI
├── requirements.txt
└── README.md
```

## Setup
```bash
pip install -r requirements.txt
```

## Run Preprocessing Pipeline
```bash
python src/preprocessing.py
```

## Prompt 2 — Traditional ML + TabNet

File tambahan untuk fase model:

- `notebooks/02_traditional_ml.ipynb`  
  Melatih Logistic Regression, SVM RBF, Decision Tree, Random Forest, XGBoost, LightGBM, dan CatBoost. Notebook ini membuat comparison table, memilih model terbaik berdasarkan F1 dan AUC-ROC, melakukan RandomizedSearchCV, membuat feature importance top 10, menyimpan `models/best_model.pkl`, dan menjalankan contoh SHAP untuk 5 applicant.

- `notebooks/03_dnn_tabnet.ipynb`  
  Melatih TabNet dengan early stopping, membuat loss curve, mengevaluasi metrik yang sama, membandingkan TabNet dengan model terbaik traditional ML, dan menyimpan `models/tabnet_model.zip`.

- `src/explainability.py`  
  Berisi fungsi `generate_shap_explanation()` dan `get_decline_reasons()` untuk menghasilkan alasan keputusan model berbasis SHAP.

Urutan jalan:

```bash
python src/preprocessing.py
jupyter notebook notebooks/02_traditional_ml.ipynb
jupyter notebook notebooks/03_dnn_tabnet.ipynb
```