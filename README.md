# CrediSense AI — Loan Approval System

CreditSense AI adalah aplikasi demo credit risk untuk membantu simulasi keputusan pengajuan pinjaman. Sistem ini memakai model machine learning untuk memprediksi risiko gagal bayar, decision engine berbasis business rules untuk menentukan status pengajuan, serta insight generator untuk memberi penjelasan yang lebih mudah dipahami oleh nasabah.

## Dataset

Dataset yang digunakan adalah `credit_risk_dataset.csv` dengan 32.581 baris dan 12 kolom. Target prediksi adalah `loan_status`, yaitu:

- `0`: nasabah tidak default
- `1`: nasabah default

Sumber dataset: Kaggle Credit Risk Prediction Dataset  
https://www.kaggle.com/datasets/laotse/credit-risk-dataset

## Arsitektur Sistem

```text
User / Streamlit Frontend
        |
        v
Orchestrator API :8000
        |
        +--> Credit Scoring Agent :8001
        |       Menghasilkan risk_score, risk_category, dan feature importance
        |
        +--> Decision Engine Agent :8002
        |       Menentukan APPROVED, DECLINED, atau REVIEW berdasarkan business rules
        |
        +--> Insight Generator Agent :8003
                Menghasilkan ringkasan keputusan, alasan risiko, tips, dan timeline
```

## Struktur Project

```text
Tubes-Kecerdasan-Mesin-main/
├── agents/
│   ├── agent1_scoring.py
│   ├── agent2_decision.py
│   └── agent3_insight.py
├── api/
│   └── orchestrator.py
├── data/
│   ├── raw/
│   └── processed/
├── frontend/
│   ├── app.py
│   └── components/
│       └── risk_gauge.py
├── models/
├── notebooks/
│   ├── 01_eda.ipynb
│   ├── 02_traditional_ml.ipynb
│   └── 03_dnn_tabnet.ipynb
├── scripts/
│   └── train_model.py
├── src/
│   ├── preprocessing.py
│   ├── features.py
│   └── explainability.py
├── tests/
│   └── test_agents.py
├── docker-compose.yml
├── run_all.sh
├── requirements.txt
└── README.md
```

## Instalasi

1. Buat virtual environment.

```bash
python -m venv .venv
```

2. Aktifkan virtual environment.

Windows PowerShell:

```bash
.venv\Scripts\Activate.ps1
```

Git Bash / Linux / macOS:

```bash
source .venv/bin/activate
```

3. Install dependency.

```bash
pip install -r requirements.txt
```

4. Siapkan file environment.

```bash
cp .env.example .env
```

Jika ingin memakai LLM asli, isi API key di `.env`:

```bash
ANTHROPIC_API_KEY=isi_api_key_di_sini
```

Jika API key belum ada, sistem tetap bisa berjalan memakai fallback response berbasis rules.

## Persiapan Data dan Model

Jalankan preprocessing:

```bash
python src/preprocessing.py
```

Latih model default untuk kebutuhan demo API:

```bash
python scripts/train_model.py
```

Notebook yang tersedia:

```text
notebooks/01_eda.ipynb              EDA dataset
notebooks/02_traditional_ml.ipynb   Eksperimen model traditional ML
notebooks/03_dnn_tabnet.ipynb       Implementasi TabNet
```

## Cara Menjalankan Semua Service

### Opsi 1 — Pakai Script

Git Bash / Linux / macOS:

```bash
chmod +x run_all.sh
./run_all.sh
```

Service yang berjalan:

```text
Agent 1 Credit Scoring    http://localhost:8001
Agent 2 Decision Engine   http://localhost:8002
Agent 3 Insight Generator http://localhost:8003
Orchestrator API          http://localhost:8000
Frontend Streamlit        http://localhost:8501
```

### Opsi 2 — Jalankan Manual

Buka beberapa terminal, lalu jalankan:

```bash
uvicorn agents.agent1_scoring:app --port 8001
uvicorn agents.agent2_decision:app --port 8002
uvicorn agents.agent3_insight:app --port 8003
uvicorn api.orchestrator:app --port 8000
streamlit run frontend/app.py --server.port 8501
```

### Opsi 3 — Docker Compose untuk Backend

```bash
docker compose up --build
```

Frontend tetap bisa dijalankan terpisah:

```bash
streamlit run frontend/app.py --server.port 8501
```

Jika memakai Docker Compose, ubah Orchestrator URL di sidebar frontend menjadi:

```text
http://localhost:8000
```

## Cara Mengakses Frontend

Buka browser ke:

```text
http://localhost:8501
```

Fitur frontend:

- Form input data nasabah dengan layout dua kolom
- Preset contoh Low Risk Applicant dan High Risk Applicant
- Perhitungan otomatis `loan_percent_income`
- Tombol pengajuan pinjaman ke Orchestrator API
- Status hasil besar: DISETUJUI, DITOLAK, atau DALAM REVIEW
- Risk score gauge chart
- Top 5 faktor risiko dalam horizontal bar chart
- Insight keputusan dan tips perbaikan
- Detail response API untuk kebutuhan demo teknis

## Dokumentasi API Ringkas

### Health Check Orchestrator

```http
GET /health
```

Response:

```json
{
  "status": "ok",
  "service": "orchestrator",
  "agents": {
    "agent1_scoring": "http://localhost:8001",
    "agent2_decision": "http://localhost:8002",
    "agent3_insight": "http://localhost:8003"
  }
}
```

### Apply Loan

```http
POST /loan/apply
```

Request body:

```json
{
  "person_age": 40,
  "person_income": 120000,
  "person_home_ownership": "OWN",
  "person_emp_length": 12,
  "loan_intent": "EDUCATION",
  "loan_amnt": 5000,
  "loan_int_rate": 7.5,
  "loan_percent_income": 0.04,
  "cb_person_default_on_file": "N",
  "cb_person_cred_hist_length": 15
}
```

Response utama:

```json
{
  "application_id": "ABC123DEF456",
  "scoring": {
    "risk_score": 0.12,
    "risk_category": "LOW",
    "model_used": "LGBMClassifier",
    "feature_importances": {}
  },
  "decision": {
    "decision": "APPROVED",
    "confidence": 0.9,
    "decline_reasons": [],
    "conditions": [],
    "max_approved_amount": 5000
  },
  "insight": {
    "summary": "Ringkasan keputusan untuk nasabah.",
    "improvement_tips": [],
    "estimated_approval_timeline": "1-3 hari kerja",
    "risk_explanation": "Penjelasan faktor risiko."
  },
  "timestamp": "2026-06-03T10:00:00"
}
```

### Cek Status Pengajuan

```http
GET /loan/status/{application_id}
```

Response:

```json
{
  "application_id": "ABC123DEF456",
  "status": "APPROVED",
  "decision": {},
  "scoring": {},
  "timestamp": "2026-06-03T10:00:00"
}
```

## Testing

Jalankan seluruh test:

```bash
pytest
```

Test mencakup:

- health check tiap service
- skenario applicant low risk
- skenario applicant high risk
- validasi input invalid
- pipeline orchestrator end-to-end dengan mock agent call

## Anggota Kelompok

- 2472019
- 2472030
- 2472039

## Catatan Demo

Sebelum demo, pastikan backend sudah aktif dan model sudah tersedia. Jika model belum ada, jalankan:

```bash
python src/preprocessing.py
python scripts/train_model.py
```

Lalu jalankan aplikasi:

```bash
./run_all.sh
```
