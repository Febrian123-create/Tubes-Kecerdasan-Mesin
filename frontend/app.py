"""Streamlit dashboard for the CrediSense AI loan approval demo."""

from __future__ import annotations

import os
from typing import Any

import httpx
import pandas as pd
import plotly.express as px
import streamlit as st

from components.risk_gauge import create_risk_gauge

ORCHESTRATOR_URL = os.getenv("ORCHESTRATOR_URL", "http://localhost:8000")

st.set_page_config(
    page_title="CrediSense AI — Loan Approval System",
    page_icon="💳",
    layout="wide",
    initial_sidebar_state="expanded",
)

st.markdown(
    """
    <style>
        .main {background: #f8fafc;}
        .block-container {padding-top: 2rem; padding-bottom: 3rem; max-width: 1180px;}
        .hero-card {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #334155 100%);
            padding: 30px 34px;
            border-radius: 24px;
            color: white;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.18);
            margin-bottom: 22px;
        }
        .hero-card h1 {margin: 0; font-size: 2.25rem; letter-spacing: -0.03em;}
        .hero-card p {margin: 10px 0 0 0; color: #cbd5e1; font-size: 1rem; max-width: 760px;}
        .metric-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 18px 20px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
            min-height: 112px;
        }
        .metric-label {font-size: 0.82rem; color: #64748b; margin-bottom: 8px;}
        .metric-value {font-size: 1.45rem; font-weight: 700; color: #0f172a; line-height: 1.2;}
        .status-approved, .status-declined, .status-review {
            border-radius: 22px;
            padding: 24px 28px;
            color: white;
            margin: 16px 0 22px 0;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
        }
        .status-approved {background: linear-gradient(135deg, #16a34a, #15803d);}
        .status-declined {background: linear-gradient(135deg, #dc2626, #991b1b);}
        .status-review {background: linear-gradient(135deg, #f59e0b, #b45309);}
        .status-title {font-size: 1.85rem; font-weight: 800; margin-bottom: 6px;}
        .status-subtitle {font-size: 0.98rem; opacity: 0.92;}
        .section-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.05);
            margin-bottom: 16px;
        }
        .tip-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px 14px;
            margin-bottom: 10px;
            color: #0f172a;
        }
        div[data-testid="stForm"] {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 22px;
            padding: 24px 24px 10px 24px;
            box-shadow: 0 12px 34px rgba(15, 23, 42, 0.07);
        }
        .small-muted {color: #64748b; font-size: 0.88rem;}
    </style>
    """,
    unsafe_allow_html=True,
)

PRESETS: dict[str, dict[str, Any]] = {
    "Manual Input": {
        "person_age": 32,
        "person_income": 60000,
        "person_home_ownership": "RENT",
        "person_emp_length": 5.0,
        "cb_person_default_on_file": "N",
        "cb_person_cred_hist_length": 6,
        "loan_amnt": 10000,
        "loan_intent": "PERSONAL",
        "loan_grade": "B",
        "loan_int_rate": 11.5,
    },
    "Low Risk Applicant": {
        "person_age": 40,
        "person_income": 120000,
        "person_home_ownership": "OWN",
        "person_emp_length": 12.0,
        "cb_person_default_on_file": "N",
        "cb_person_cred_hist_length": 15,
        "loan_amnt": 5000,
        "loan_intent": "EDUCATION",
        "loan_grade": "A",
        "loan_int_rate": 7.5,
    },
    "High Risk Applicant": {
        "person_age": 22,
        "person_income": 18000,
        "person_home_ownership": "RENT",
        "person_emp_length": 0.5,
        "cb_person_default_on_file": "Y",
        "cb_person_cred_hist_length": 1,
        "loan_amnt": 14000,
        "loan_intent": "PERSONAL",
        "loan_grade": "G",
        "loan_int_rate": 21.5,
    },
}

GRADE_DESCRIPTIONS = {
    "A": "A — risiko sangat rendah, bunga rendah",
    "B": "B — risiko rendah",
    "C": "C — risiko cukup baik",
    "D": "D — risiko menengah",
    "E": "E — risiko tinggi",
    "F": "F — risiko sangat tinggi",
    "G": "G — risiko tertinggi",
}

GRADE_DEFAULT_RATE = {
    "A": 7.5,
    "B": 11.5,
    "C": 13.5,
    "D": 15.5,
    "E": 17.2,
    "F": 18.8,
    "G": 21.5,
}

HOME_OPTIONS = ["RENT", "OWN", "MORTGAGE", "OTHER"]
INTENT_OPTIONS = [
    "PERSONAL",
    "EDUCATION",
    "MEDICAL",
    "VENTURE",
    "HOMEIMPROVEMENT",
    "DEBTCONSOLIDATION",
]


def format_money(value: float) -> str:
    return f"Rp {value:,.0f}".replace(",", ".")


def call_orchestrator(payload: dict[str, Any]) -> dict[str, Any]:
    with httpx.Client(timeout=60.0) as client:
        response = client.post(f"{ORCHESTRATOR_URL}/loan/apply", json=payload)
        response.raise_for_status()
        return response.json()


def render_status(decision: str, confidence: float) -> None:
    decision = decision.upper()
    if decision == "APPROVED":
        css_class = "status-approved"
        title = "✅ DISETUJUI"
        subtitle = "Pengajuan memenuhi kriteria kelayakan awal dan dapat dilanjutkan ke proses pencairan."
    elif decision == "DECLINED":
        css_class = "status-declined"
        title = "❌ DITOLAK"
        subtitle = "Pengajuan belum memenuhi kriteria risiko kredit yang ditetapkan saat ini."
    else:
        css_class = "status-review"
        title = "🔍 DALAM REVIEW"
        subtitle = "Pengajuan membutuhkan pemeriksaan manual oleh analis kredit sebelum keputusan final."

    st.markdown(
        f"""
        <div class="{css_class}">
            <div class="status-title">{title}</div>
            <div class="status-subtitle">{subtitle} Confidence sistem: {confidence:.0%}</div>
        </div>
        """,
        unsafe_allow_html=True,
    )


def render_metric(label: str, value: str) -> None:
    st.markdown(
        f"""
        <div class="metric-card">
            <div class="metric-label">{label}</div>
            <div class="metric-value">{value}</div>
        </div>
        """,
        unsafe_allow_html=True,
    )


def render_feature_importance(feature_importances: dict[str, float]) -> None:
    st.markdown("<div class='section-card'>", unsafe_allow_html=True)
    st.subheader("Top 5 Faktor Risiko")

    if not feature_importances:
        st.info("Faktor risiko belum tersedia dari model.")
        st.markdown("</div>", unsafe_allow_html=True)
        return

    df = pd.DataFrame(
        {
            "Fitur": list(feature_importances.keys()),
            "Pengaruh": [float(v) for v in feature_importances.values()],
        }
    ).sort_values("Pengaruh", ascending=True)

    fig = px.bar(
        df,
        x="Pengaruh",
        y="Fitur",
        orientation="h",
        text="Pengaruh",
        color="Pengaruh",
        color_continuous_scale="Blues",
    )
    fig.update_traces(texttemplate="%{text:.3f}", textposition="outside")
    fig.update_layout(
        height=315,
        margin={"l": 10, "r": 20, "t": 10, "b": 10},
        showlegend=False,
        coloraxis_showscale=False,
        plot_bgcolor="rgba(0,0,0,0)",
        paper_bgcolor="rgba(0,0,0,0)",
        xaxis_title="Impact",
        yaxis_title="",
    )
    st.plotly_chart(fig, use_container_width=True)
    st.markdown("</div>", unsafe_allow_html=True)


def render_insight(insight: dict[str, Any], decision: str) -> None:
    st.markdown("<div class='section-card'>", unsafe_allow_html=True)
    st.subheader("Insight Nasabah")

    summary = insight.get("summary", "Insight belum tersedia.")
    explanation = insight.get("risk_explanation", "")
    timeline = insight.get("estimated_approval_timeline", "-")

    if decision == "APPROVED":
        st.success(summary)
    elif decision == "DECLINED":
        st.warning(summary)
    else:
        st.info(summary)

    if explanation:
        st.write(explanation)

    st.markdown(f"<p class='small-muted'>Estimasi timeline: <b>{timeline}</b></p>", unsafe_allow_html=True)

    tips = insight.get("improvement_tips", [])
    if tips:
        st.markdown("#### Tips Perbaikan / Tindak Lanjut")
        for tip in tips:
            st.markdown(f"<div class='tip-card'>✓ {tip}</div>", unsafe_allow_html=True)

    st.markdown("</div>", unsafe_allow_html=True)


with st.sidebar:
    st.title("CrediSense AI")
    st.caption("Demo sistem approval pinjaman berbasis credit risk model dan agentic workflow.")

    preset_name = st.selectbox(
        "Preset contoh",
        list(PRESETS.keys()),
        help="Pilih contoh data untuk auto-fill form demo.",
    )

    st.divider()
    st.markdown("#### Koneksi API")
    orchestrator_url = st.text_input("Orchestrator URL", ORCHESTRATOR_URL)
    ORCHESTRATOR_URL = orchestrator_url.rstrip("/")

    if st.button("Cek Health API", use_container_width=True):
        try:
            response = httpx.get(f"{ORCHESTRATOR_URL}/health", timeout=10.0)
            response.raise_for_status()
            st.success("Orchestrator aktif.")
        except Exception as exc:
            st.error(f"API belum aktif: {exc}")

    st.divider()
    st.markdown("#### Alur Sistem")
    st.markdown(
        """
        1. Nasabah mengisi form  
        2. Sistem menghitung risiko default  
        3. Rule engine menentukan keputusan  
        4. Insight generator memberi penjelasan
        """
    )

preset = PRESETS[preset_name]
preset_key = preset_name.lower().replace(" ", "_")

st.markdown(
    """
    <div class="hero-card">
        <h1>CrediSense AI — Loan Approval System</h1>
        <p>Dashboard demo untuk simulasi persetujuan pinjaman. Isi data nasabah, ajukan pinjaman, lalu sistem akan menampilkan keputusan, skor risiko, faktor utama, dan insight yang mudah dipahami.</p>
    </div>
    """,
    unsafe_allow_html=True,
)

with st.form("loan_application_form"):
    st.subheader("Form Data Nasabah")
    left, right = st.columns(2, gap="large")

    with left:
        person_age = st.slider("Usia", 18, 70, int(preset["person_age"]), key=f"age_{preset_key}")
        person_income = st.number_input(
            "Pendapatan Tahunan",
            min_value=1_000,
            max_value=1_000_000,
            value=int(preset["person_income"]),
            step=1_000,
            format="%d",
            key=f"income_{preset_key}",
            help="Masukkan nilai pendapatan tahunan. Pada demo ini angka mengikuti skala dataset.",
        )
        st.caption(f"Format tampilan: {format_money(float(person_income))}")
        person_home_ownership = st.selectbox(
            "Status Kepemilikan Rumah",
            HOME_OPTIONS,
            index=HOME_OPTIONS.index(preset["person_home_ownership"]),
            key=f"home_{preset_key}",
        )
        person_emp_length = st.slider(
            "Lama Bekerja (tahun)",
            0.0,
            40.0,
            float(preset["person_emp_length"]),
            step=0.5,
            key=f"emp_{preset_key}",
        )
        cb_person_default_on_file = st.radio(
            "Pernah Default Sebelumnya?",
            ["N", "Y"],
            horizontal=True,
            index=["N", "Y"].index(preset["cb_person_default_on_file"]),
            key=f"default_{preset_key}",
        )
        cb_person_cred_hist_length = st.slider(
            "Panjang Riwayat Kredit (tahun)",
            0,
            30,
            int(preset["cb_person_cred_hist_length"]),
            key=f"hist_{preset_key}",
        )

    with right:
        loan_amnt = st.slider(
            "Jumlah Pinjaman",
            1_000,
            50_000,
            int(preset["loan_amnt"]),
            step=500,
            key=f"amount_{preset_key}",
        )
        st.caption(f"Format tampilan: {format_money(float(loan_amnt))}")
        loan_intent = st.selectbox(
            "Tujuan Pinjaman",
            INTENT_OPTIONS,
            index=INTENT_OPTIONS.index(preset["loan_intent"]),
            key=f"intent_{preset_key}",
        )
        loan_grade = st.selectbox(
            "Grade Pinjaman",
            list(GRADE_DESCRIPTIONS.keys()),
            index=list(GRADE_DESCRIPTIONS.keys()).index(preset["loan_grade"]),
            format_func=lambda x: GRADE_DESCRIPTIONS[x],
            key=f"grade_{preset_key}",
        )
        suggested_rate = float(preset.get("loan_int_rate", GRADE_DEFAULT_RATE[loan_grade]))
        loan_int_rate = st.slider(
            "Bunga Pinjaman (%)",
            5.0,
            25.0,
            suggested_rate,
            step=0.1,
            key=f"rate_{preset_key}",
            help="Grade digunakan sebagai panduan simulasi. Nilai bunga tetap bisa disesuaikan.",
        )
        loan_percent_income = round(float(loan_amnt) / float(person_income), 4)
        st.metric("Loan Percent Income", f"{loan_percent_income:.1%}")
        st.caption("Rasio ini dihitung otomatis dari jumlah pinjaman dibagi pendapatan tahunan.")

    submitted = st.form_submit_button("Ajukan Pinjaman", use_container_width=True, type="primary")

if submitted:
    payload = {
        "person_age": float(person_age),
        "person_income": float(person_income),
        "person_home_ownership": person_home_ownership,
        "person_emp_length": float(person_emp_length),
        "loan_intent": loan_intent,
        "loan_amnt": float(loan_amnt),
        "loan_int_rate": float(loan_int_rate),
        "loan_percent_income": loan_percent_income,
        "cb_person_default_on_file": cb_person_default_on_file,
        "cb_person_cred_hist_length": float(cb_person_cred_hist_length),
    }

    with st.spinner("Memproses pengajuan pinjaman..."):
        try:
            st.session_state["last_result"] = call_orchestrator(payload)
            st.session_state["last_payload"] = payload
        except httpx.HTTPStatusError as exc:
            detail = exc.response.text
            st.error(f"Request ditolak API ({exc.response.status_code}). Detail: {detail}")
        except httpx.RequestError as exc:
            st.error(
                "Tidak bisa terhubung ke orchestrator. Pastikan semua service sudah berjalan "
                f"di {ORCHESTRATOR_URL}. Detail: {exc}"
            )
        except Exception as exc:
            st.error(f"Terjadi error saat memproses pengajuan: {exc}")

result = st.session_state.get("last_result")

if result:
    scoring = result.get("scoring", {})
    decision = result.get("decision", {})
    insight = result.get("insight", {})

    decision_label = decision.get("decision", "REVIEW").upper()
    risk_score = float(scoring.get("risk_score", 0.0))
    confidence = float(decision.get("confidence", 0.0))

    st.divider()
    st.subheader("Hasil Keputusan")
    render_status(decision_label, confidence)

    c1, c2, c3, c4 = st.columns(4)
    with c1:
        render_metric("Application ID", result.get("application_id", "-"))
    with c2:
        render_metric("Risk Category", scoring.get("risk_category", "-"))
    with c3:
        render_metric("Model Used", scoring.get("model_used", "-"))
    with c4:
        max_amount = float(decision.get("max_approved_amount", 0.0))
        render_metric("Max Approved", format_money(max_amount))

    chart_col, factor_col = st.columns([0.9, 1.1], gap="large")
    with chart_col:
        st.markdown("<div class='section-card'>", unsafe_allow_html=True)
        st.subheader("Risk Score Gauge")
        st.plotly_chart(create_risk_gauge(risk_score), use_container_width=True)
        st.markdown("</div>", unsafe_allow_html=True)

    with factor_col:
        render_feature_importance(scoring.get("feature_importances", {}))

    render_insight(insight, decision_label)

    with st.expander("Detail teknis hasil API"):
        st.json(result)

    if decision.get("decline_reasons"):
        with st.expander("Alasan Penolakan"):
            for reason in decision["decline_reasons"]:
                st.write(f"- {reason}")

    if decision.get("conditions"):
        with st.expander("Syarat / Catatan Tambahan"):
            for condition in decision["conditions"]:
                st.write(f"- {condition}")
else:
    st.info("Isi form lalu klik Ajukan Pinjaman untuk melihat hasil simulasi.")
