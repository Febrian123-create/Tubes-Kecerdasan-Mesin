"""
Agent 3 -- LLM Insight Generator (port 8003)

Calls the Anthropic Claude API to generate a human-readable explanation
of the credit decision in Bahasa Indonesia, including actionable improvement tips.

If ANTHROPIC_API_KEY is not set, a rule-based fallback is returned so the
service is usable in offline/test environments without an API key.
"""

import json
import os
from typing import List

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel

app = FastAPI(title="LLM Insight Agent", version="1.0.0")

_SYSTEM_PROMPT = (
    "Kamu adalah financial advisor AI untuk sebuah bank digital Indonesia. "
    "Tugasmu menjelaskan keputusan kredit kepada nasabah dengan bahasa yang sopan, "
    "mudah dipahami, dan memberikan saran konkret yang actionable. "
    "Selalu gunakan Bahasa Indonesia yang formal tapi ramah. "
    "Jangan pernah menyebutkan angka risk score secara langsung ke nasabah.\n\n"
    "Untuk APPROVED: fokus pada selamat dan syarat-syarat yang perlu dipenuhi.\n"
    "Untuk DECLINED: fokus pada empati, penjelasan alasan, dan langkah perbaikan konkret.\n"
    "Untuk REVIEW: jelaskan bahwa sedang dalam proses review dan perkiraan waktu.\n\n"
    "Format output HANYA berupa JSON (tanpa markdown) dengan key berikut:\n"
    "  summary                    : string (2-3 kalimat ringkasan keputusan)\n"
    "  improvement_tips           : array of string (3-5 tips spesifik dan actionable)\n"
    "  estimated_approval_timeline: string (contoh: '3 bulan', '1-3 hari kerja')\n"
    "  risk_explanation           : string (penjelasan faktor risiko dalam bahasa sederhana)"
)


# ── Schemas ────────────────────────────────────────────────────────────────────

class InsightRequest(BaseModel):
    decision: str
    applicant_data: dict
    decline_reasons: List[str]
    risk_score: float
    feature_importances: dict


class InsightResult(BaseModel):
    summary: str
    improvement_tips: List[str]
    estimated_approval_timeline: str
    risk_explanation: str


# ── Helpers ────────────────────────────────────────────────────────────────────

def _build_prompt(data: InsightRequest) -> str:
    app_data  = data.applicant_data
    top_feats = list(data.feature_importances.keys())[:3]

    lines = [
        f"Keputusan kredit: {data.decision}",
        "",
        "Data nasabah:",
        f"  - Usia              : {app_data.get('person_age')} tahun",
        f"  - Pendapatan tahunan: {float(app_data.get('person_income', 0)):,.0f} USD",
        f"  - Kepemilikan rumah : {app_data.get('person_home_ownership', 'N/A')}",
        f"  - Lama bekerja      : {app_data.get('person_emp_length', 0)} tahun",
        f"  - Tujuan pinjaman   : {app_data.get('loan_intent', 'N/A')}",
        f"  - Jumlah pinjaman   : {float(app_data.get('loan_amnt', 0)):,.0f} USD",
        f"  - Bunga             : {app_data.get('loan_int_rate', 0)}%",
        f"  - Riwayat kredit    : {app_data.get('cb_person_cred_hist_length', 0)} tahun",
        f"  - Default sebelumnya: {app_data.get('cb_person_default_on_file', 'N')}",
        "",
        "Alasan keputusan: " + (", ".join(data.decline_reasons) if data.decline_reasons else "Profil kredit memenuhi syarat"),
        "Faktor utama penilaian: " + (", ".join(top_feats) if top_feats else "analisis komprehensif"),
        "",
        "Berikan respons dalam format JSON yang diminta.",
    ]
    return "\n".join(lines)


def _fallback(data: InsightRequest) -> InsightResult:
    """Rule-based fallback when no LLM API key is configured."""
    decision = data.decision.upper()

    if decision == "APPROVED":
        return InsightResult(
            summary=(
                "Selamat! Pengajuan pinjaman Anda telah disetujui. "
                "Kami menghargai kepercayaan Anda dan berkomitmen untuk memberikan layanan terbaik. "
                "Silakan periksa syarat dan ketentuan yang berlaku sebelum menandatangani perjanjian."
            ),
            improvement_tips=[
                "Pastikan pembayaran cicilan dilakukan tepat waktu setiap bulannya",
                "Simpan salinan perjanjian kredit di tempat yang aman",
                "Hubungi kami segera jika mengalami kesulitan pembayaran sebelum tanggal jatuh tempo",
                "Manfaatkan layanan auto-debit untuk menghindari keterlambatan pembayaran",
            ],
            estimated_approval_timeline="Dana akan dicairkan dalam 1-3 hari kerja setelah penandatanganan dokumen",
            risk_explanation=(
                "Profil keuangan Anda menunjukkan kemampuan pembayaran yang baik "
                "berdasarkan pendapatan, riwayat kredit, dan beban utang yang ada."
            ),
        )

    if decision == "DECLINED":
        reason_text = ". ".join(data.decline_reasons) if data.decline_reasons else "Profil risiko tidak memenuhi kriteria"
        return InsightResult(
            summary=(
                f"Kami mohon maaf, pengajuan pinjaman Anda belum dapat kami setujui saat ini. "
                f"{reason_text}. "
                "Namun kami berkomitmen untuk membantu Anda meningkatkan kelayakan kredit di masa mendatang."
            ),
            improvement_tips=[
                "Kurangi rasio utang terhadap pendapatan dengan melunasi cicilan yang ada terlebih dahulu",
                "Pertahankan pembayaran tepat waktu selama minimal 6 bulan ke depan untuk memperbaiki profil kredit",
                "Pertimbangkan mengajukan pinjaman dengan jumlah yang lebih kecil sesuai kemampuan pendapatan",
                "Tingkatkan riwayat kredit dengan menggunakan kartu kredit secara bijak dan konsisten",
                "Konsultasikan kondisi keuangan Anda dengan konsultan keuangan kami secara gratis",
            ],
            estimated_approval_timeline="6-12 bulan setelah perbaikan profil keuangan yang konsisten",
            risk_explanation=(
                "Pengajuan ini tidak memenuhi beberapa kriteria penilaian kredit kami "
                "berdasarkan analisis komprehensif terhadap profil keuangan dan riwayat kredit Anda."
            ),
        )

    # REVIEW
    return InsightResult(
        summary=(
            "Pengajuan pinjaman Anda sedang dalam proses review lebih lanjut oleh tim analis kredit kami. "
            "Hal ini dilakukan untuk memastikan semua informasi telah terverifikasi dengan baik. "
            "Kami akan menghubungi Anda dalam waktu dekat melalui kontak yang terdaftar."
        ),
        improvement_tips=[
            "Siapkan dokumen pendukung seperti slip gaji atau laporan keuangan 3 bulan terakhir",
            "Pastikan nomor telepon dan alamat email Anda aktif dan dapat dihubungi",
            "Lengkapi semua persyaratan dokumen yang diminta dalam portal aplikasi",
        ],
        estimated_approval_timeline="3-5 hari kerja untuk proses review manual",
        risk_explanation=(
            "Pengajuan Anda memerlukan verifikasi tambahan untuk memastikan kelengkapan "
            "dan keakuratan informasi yang diberikan."
        ),
    )


# ── Endpoint ───────────────────────────────────────────────────────────────────

@app.post("/insight", response_model=InsightResult)
async def generate_insight(data: InsightRequest):
    api_key = os.getenv("ANTHROPIC_API_KEY", "").strip()
    if not api_key:
        return _fallback(data)

    try:
        from anthropic import Anthropic
        client = Anthropic(api_key=api_key)

        message = client.messages.create(
            model="claude-haiku-4-5-20251001",
            max_tokens=1024,
            system=_SYSTEM_PROMPT,
            messages=[{"role": "user", "content": _build_prompt(data)}],
        )

        raw = message.content[0].text.strip()
        # Strip optional markdown code fences
        if "```json" in raw:
            raw = raw.split("```json", 1)[1].split("```", 1)[0].strip()
        elif "```" in raw:
            raw = raw.split("```", 1)[1].split("```", 1)[0].strip()

        parsed = json.loads(raw)

        return InsightResult(
            summary=parsed.get("summary", ""),
            improvement_tips=parsed.get("improvement_tips", []),
            estimated_approval_timeline=parsed.get("estimated_approval_timeline", ""),
            risk_explanation=parsed.get("risk_explanation", ""),
        )

    except json.JSONDecodeError:
        return _fallback(data)
    except Exception as exc:
        raise HTTPException(status_code=500, detail=f"LLM call failed: {exc}")


@app.get("/health")
async def health():
    return {
        "status": "ok",
        "agent": "insight_generator",
        "llm_configured": bool(os.getenv("ANTHROPIC_API_KEY", "").strip()),
    }


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8003)
