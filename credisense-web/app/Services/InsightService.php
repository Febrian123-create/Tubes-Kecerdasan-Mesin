<?php

namespace App\Services;

use App\Models\LoanApplication;
use App\Models\LlmInsight;
use Illuminate\Support\Facades\Log;

class InsightService
{
    public function generate(LoanApplication $app): void
    {
        $app->load(['scoringResult', 'loanDecision', 'user']);

        $score    = $app->scoringResult;
        $decision = $app->loanDecision;
        $user     = $app->user;

        // ── Annuity payment plan ─────────────────────────────────────────────
        $approvedAmount = $decision->approved_amount ?? $app->loan_amnt;
        $annualRate     = $app->loan_int_rate / 100;
        $monthlyRate    = $annualRate / 12;
        $tenors         = [12, 24, 36];
        $paymentPlan    = [];
        foreach ($tenors as $n) {
            if ($monthlyRate > 0) {
                $m = $approvedAmount * ($monthlyRate * pow(1 + $monthlyRate, $n))
                    / (pow(1 + $monthlyRate, $n) - 1);
            } else {
                $m = $approvedAmount / $n;
            }
            $paymentPlan[] = [
                'tenor_months'     => $n,
                'monthly_payment'  => round($m, 2),
                'total_payment'    => round($m * $n, 2),
                'total_interest'   => round($m * $n - $approvedAmount, 2),
            ];
        }

        // ── Top feature importance lines ─────────────────────────────────────
        $topFeatures = '';
        if ($score && $score->feature_importances) {
            $fi = $score->feature_importances;
            arsort($fi);
            $lines = [];
            foreach (array_slice($fi, 0, 3, true) as $feat => $val) {
                $lines[] = "  - {$feat}: " . number_format($val, 4);
            }
            $topFeatures = implode("\n", $lines);
        }

        // ── Derive human-readable loan grade label ────────────────────────────
        $gradeLabelMap = [
            1 => 'A (Sangat Baik — bunga < 9.24%)',
            2 => 'B (Baik — bunga 9.24–12.24%)',
            3 => 'C (Cukup Baik — bunga 12.24–14.40%)',
            4 => 'D (Rata-rata — bunga 14.40–16.07%)',
            5 => 'E (Di Bawah Rata-rata — bunga 16.07–17.68%)',
            6 => 'F (Buruk — bunga 17.68–19.35%)',
            7 => 'G (Sangat Buruk — bunga > 19.35%)',
        ];
        $gradeLabel = $gradeLabelMap[$app->loan_grade] ?? "Grade {$app->loan_grade}";

        $lpiPct     = number_format(($app->loan_percent_income ?? 0) * 100, 1);
        $defaultStr = $app->cb_person_default_on_file === 'Y' ? 'Pernah (tercatat di SLIK OJK)' : 'Tidak pernah';
        $empStr     = $app->person_emp_length !== null ? "{$app->person_emp_length} tahun" : 'Tidak diketahui';

        // ── Build the LLM prompt ──────────────────────────────────────────────
        $prompt = <<<PROMPT
Kamu adalah analis kredit senior di CrediSense AI, platform penilaian kredit berbasis kecerdasan buatan untuk pasar Indonesia.

=== KONTEKS SISTEM CREDISENSE AI ===
CrediSense AI adalah platform pinjaman online (pinjol) tanpa agunan (KTA) yang menggunakan machine learning untuk menilai risiko kredit.

Cara kerja sistem:
1. Peminjam mengisi data diri dan mengajukan pinjaman.
2. Admin memverifikasi riwayat kredit peminjam dari SLIK OJK (Sistem Layanan Informasi Keuangan).
3. Model LightGBM menghitung skor risiko (0–1) berdasarkan 11 fitur peminjam.
4. Sistem Business Rule Engine memutuskan APPROVED / DECLINED / REVIEW berdasarkan skor dan aturan bisnis.
5. Jika APPROVED, kamu (AI) membuat analisis kredit personal untuk peminjam.

Cara peringkat kredit (loan_grade) ditentukan:
- BUKAN dari OJK secara langsung — grade diturunkan otomatis dari suku bunga menggunakan threshold yang dikalibrasi dari data historis.
- Grade A (risiko terendah) = bunga di bawah 9.24% per tahun
- Grade B = 9.24–12.24%, Grade C = 12.24–14.40%, Grade D = 14.40–16.07%
- Grade E = 16.07–17.68%, Grade F = 17.68–19.35%, Grade G (risiko tertinggi) = di atas 19.35%
- Prinsipnya: suku bunga mencerminkan risiko peminjam — semakin tinggi bunga yang diajukan, semakin tinggi gradenya, semakin ketat evaluasinya.

Skema cicilan yang digunakan: ANUITAS (bukan flat rate).
- Cicilan tetap setiap bulan, namun komposisi pokok vs bunga berubah tiap bulan.
- Lebih adil ke peminjam dibanding flat rate karena total bunga lebih kecil.
- Rumus: M = P × [r(1+r)^n] / [(1+r)^n − 1], di mana r = bunga bulanan, n = tenor bulan.

Aturan bisnis keputusan kredit:
- DECLINED otomatis jika: rasio pinjaman/pendapatan > 50%, atau skor risiko > 60%, atau pernah default + skor > 30%.
- APPROVED jika: skor risiko < 20% dan rasio pinjaman/pendapatan < 30%, atau skor 20–40% dengan grade A/B/C.
- REVIEW jika tidak memenuhi kriteria di atas (perlu evaluasi manual).
- Jumlah yang disetujui: min(jumlah_diajukan, pendapatan × 0.4 / (1 + skor_risiko)).

Faktor risiko yang dipelajari model (dari data historis kredit):
- Skor risiko dipengaruhi oleh: suku bunga, rasio pinjaman/pendapatan, peringkat kredit, riwayat default, histori kredit, pendapatan, usia, lama bekerja, tujuan pinjaman, kepemilikan rumah.

=== DATA PEMINJAM ===
Nama                   : {$user->name}
Usia                   : {$app->person_age} tahun
Pendapatan Tahunan     : Rp {$app->person_income}
Kepemilikan Rumah      : {$app->person_home_ownership}
Lama Bekerja           : {$empStr}
Riwayat Default (SLIK) : {$defaultStr}
Panjang Histori Kredit : {$app->cb_person_cred_hist_length} tahun

=== DATA PINJAMAN ===
Tujuan Penggunaan Dana : {$app->loan_intent}
Jumlah Diajukan        : Rp {$app->loan_amnt}
Jumlah Disetujui       : Rp {$approvedAmount}
Suku Bunga             : {$app->loan_int_rate}% per tahun
Peringkat Kredit       : {$gradeLabel}
Rasio Pinjaman/Pendapatan : {$lpiPct}% dari pendapatan tahunan

=== HASIL PENILAIAN MODEL AI ===
Skor Risiko     : {$score?->risk_score} dari skala 0–1 (mendekati 0 = aman, mendekati 1 = berisiko tinggi)
Kategori Risiko : {$score?->risk_category}
Model           : {$score?->model_used}
Faktor paling berpengaruh dalam keputusan (dari analisis model):
{$topFeatures}

=== KEPUTUSAN SISTEM ===
Status    : DISETUJUI
Kondisi   : {$decision?->conditions}

=== INSTRUKSI OUTPUT ===
Berikan respons HANYA dalam format JSON berikut (tanpa markdown, tanpa teks lain, langsung JSON):
{
  "summary": "2-3 kalimat ringkasan keputusan yang personal dan mudah dipahami peminjam awam. Sebut nama peminjam.",
  "approval_reasons": "3-4 alasan spesifik mengapa kredit ini disetujui. Hubungkan dengan data nyata peminjam di atas. Jangan sebut angka skor teknis.",
  "risk_notes": "1-2 catatan risiko konkret yang relevan dengan profil peminjam ini selama masa pinjaman.",
  "recommendations": "2-3 saran praktis dan spesifik untuk peminjam ini agar bisa menjaga kesehatan kredit."
}

Aturan penulisan:
- Gunakan Bahasa Indonesia yang ramah, profesional, dan mudah dipahami masyarakat umum.
- Jangan sebut angka skor risiko, nama model, atau istilah teknis ML secara langsung.
- Terjemahkan insight teknis ke bahasa manusia — misalnya "skor risiko rendah" → "profil keuangan Anda dinilai sehat".
- Jadikan analisis terasa personal, bukan template generik.
PROMPT;

        // ── Call Gemini API ───────────────────────────────────────────────────
        $apiKey  = config('services.gemini.key');
        $result  = null;
        $modelId = 'gemini-2.0-flash';
        $tokens  = null;

        if ($apiKey) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelId}:generateContent?key={$apiKey}";

                $response = \Illuminate\Support\Facades\Http::timeout(60)->post($url, [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => 1024,
                        'temperature'     => 0.4,
                    ],
                ]);

                if ($response->successful()) {
                    $body   = $response->json();
                    $raw    = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    $tokens = $body['usageMetadata']['candidatesTokenCount'] ?? null;

                    // Strip markdown fences if Gemini wraps the JSON
                    $raw    = preg_replace('/^```json\s*/i', '', trim($raw));
                    $raw    = preg_replace('/\s*```$/', '', $raw);
                    $result = json_decode($raw, true);
                } else {
                    Log::warning('[InsightService] Gemini error: ' . $response->body());
                }
            } catch (\Throwable $e) {
                Log::error('[InsightService] Exception: ' . $e->getMessage());
            }
        }

        // Fallback jika API tidak tersedia
        if (!$result) {
            $modelId = 'rule-based-fallback';
            $result  = [
                'summary'          => "Pengajuan pinjaman Anda sebesar Rp {$approvedAmount} telah DISETUJUI oleh sistem CrediSense AI berdasarkan profil kredit dan kemampuan finansial Anda.",
                'approval_reasons' => "Skor risiko kredit Anda tergolong {$score?->risk_category}. Rasio pinjaman terhadap pendapatan masih dalam batas aman. Histori kredit Anda mendukung persetujuan ini.",
                'risk_notes'       => "Pastikan cicilan dibayar tepat waktu setiap bulan untuk menjaga skor kredit Anda.",
                'recommendations'  => "Pilih tenor yang sesuai kemampuan bulanan Anda. Simpan dana darurat minimal 3x cicilan.",
            ];
        }

        LlmInsight::updateOrCreate(
            ['loan_application_id' => $app->id],
            [
                'summary'          => $result['summary'] ?? '',
                'approval_reasons' => $result['approval_reasons'] ?? '',
                'payment_plan'     => $paymentPlan,
                'risk_notes'       => $result['risk_notes'] ?? '',
                'recommendations'  => $result['recommendations'] ?? '',
                'model_used'       => $modelId,
                'tokens_used'      => $tokens,
            ]
        );
    }
}
