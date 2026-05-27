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
