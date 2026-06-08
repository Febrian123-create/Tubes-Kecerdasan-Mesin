<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('borrower.applications.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h2 class="font-bold text-lg text-gray-800">Detail Pengajuan <span class="text-blue-600">#{{ $application->id }}</span></h2>
                <p class="text-xs text-gray-400">{{ $application->created_at->format('d F Y, H:i') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="p-6 space-y-5 max-w-4xl">

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center gap-2 text-sm">
                <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Status Banner --}}
        @php
            $banners = [
                'pending'  => ['from-amber-500 to-orange-500',   'Sedang Diproses',                'Profil Anda sedang diverifikasi & dinilai oleh AI...'],
                'scored'   => ['from-purple-500 to-indigo-600',  'Sedang Diproses',                'Model AI sedang mengevaluasi profil Anda.'],
                'approved' => ['from-green-500 to-emerald-600',  'Pengajuan Disetujui ✓',          'Selamat! Kredit Anda telah disetujui.'],
                'rejected' => ['from-red-500 to-rose-600',       'Pengajuan Ditolak',              'Maaf, kredit tidak dapat disetujui saat ini.'],
                'review'   => ['from-orange-500 to-amber-600',   'Perlu Tinjauan Manual',          'Tim kami akan menghubungi Anda untuk verifikasi lebih lanjut.'],
            ];
            [$gradient, $title, $subtitle] = $banners[$application->status] ?? ['from-gray-500 to-gray-600', $application->status, ''];
        @endphp
        <div class="bg-gradient-to-r {{ $gradient }} rounded-2xl p-5 text-white shadow-md">
            <p class="font-bold text-xl">{{ $title }}</p>
            <p class="text-white/80 text-sm mt-0.5">{{ $subtitle }}</p>
            @if($application->notes)
                <p class="text-white/70 text-xs mt-2 border-t border-white/20 pt-2">{{ $application->notes }}</p>
            @endif
            @if($application->status === 'approved')
                <a href="{{ route('borrower.wallet.index') }}" class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 transition rounded-xl px-4 py-2 mt-3 text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-9 4h16a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Dana telah masuk ke Dompet Anda — Lihat Dompet
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            @endif
        </div>

        {{-- Loan summary --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="bg-slate-50 px-5 py-3 border-b border-gray-100">
                <p class="font-semibold text-gray-700 text-sm">Ringkasan Pinjaman</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-gray-100">
                @php $product = config('loan_products.'.$application->loan_product); @endphp
                @foreach([
                    ['Jumlah Diajukan',  'Rp '.number_format($application->loan_amnt)],
                    ['Produk Kredit',    $product['name'] ?? '—'],
                    ['Suku Bunga',       $application->loan_int_rate.'%/thn'],
                    ['Tenor Cicilan',    $application->loan_tenor_months ? $application->loan_tenor_months.' bulan' : '—'],
                    ['Tujuan',           $application->loan_intent],
                    ['Kepemilikan Rmh',  $application->person_home_ownership],
                ] as [$label,$value])
                <div class="bg-white px-5 py-4">
                    <p class="text-xs text-gray-400 mb-1">{{ $label }}</p>
                    <p class="font-semibold text-gray-800 text-sm">{{ $value }}</p>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Scoring --}}
        @if($application->scoringResult)
        @php $score = $application->scoringResult->risk_score; @endphp
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="font-semibold text-gray-700 mb-4">Penilaian Risiko AI</p>
            <div class="flex items-center gap-6">
                {{-- Score circle --}}
                <div class="relative w-24 h-24 shrink-0">
                    @php
                        $pct = $score * 100;
                        $color = $score < 0.3 ? '#22c55e' : ($score < 0.6 ? '#f59e0b' : '#ef4444');
                        $dash = 2 * pi() * 36;
                        $filled = $dash * (1 - $score);
                    @endphp
                    <svg class="w-24 h-24 -rotate-90" viewBox="0 0 80 80">
                        <circle cx="40" cy="40" r="36" fill="none" stroke="#f1f5f9" stroke-width="8"/>
                        <circle cx="40" cy="40" r="36" fill="none" stroke="{{ $color }}" stroke-width="8"
                            stroke-dasharray="{{ $dash }}" stroke-dashoffset="{{ $filled }}"
                            stroke-linecap="round" style="transition:stroke-dashoffset 1s ease"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="font-bold text-lg" style="color:{{ $color }}">{{ number_format($pct,1) }}%</span>
                    </div>
                </div>
                <div>
                    @php $catColor = ['LOW'=>'text-green-600','MEDIUM'=>'text-amber-600','HIGH'=>'text-orange-600','VERY_HIGH'=>'text-red-600'][$application->scoringResult->risk_category] ?? 'text-gray-600'; @endphp
                    <p class="font-bold text-xl {{ $catColor }}">{{ $application->scoringResult->risk_category }}</p>
                    <p class="text-sm text-gray-500 mt-0.5">Kategori Risiko</p>
                    <p class="text-xs text-gray-400 mt-1">Model: {{ $application->scoringResult->model_used }}</p>
                    @if($application->scoringResult->feature_importances)
                    <div class="mt-3 space-y-1">
                        @foreach(array_slice($application->scoringResult->feature_importances, 0, 3, true) as $feat => $val)
                        <div class="flex items-center gap-2 text-xs">
                            <span class="text-gray-500 w-36 truncate">{{ $feat }}</span>
                            <div class="flex-1 bg-gray-100 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full bg-blue-500" style="width:{{ min(100, $val*500) }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Decision --}}
        @if($application->loanDecision)
        @php
            $decConf = [
                'APPROVED' => ['bg-green-50 border-green-200',  'text-green-700',  '✓ Disetujui'],
                'DECLINED' => ['bg-red-50 border-red-200',      'text-red-700',    '✗ Ditolak'],
                'REVIEW'   => ['bg-orange-50 border-orange-200','text-orange-700', '⚠ Perlu Tinjauan'],
            ][$application->loanDecision->decision] ?? ['bg-gray-50 border-gray-200','text-gray-700','—'];
        @endphp
        <div class="border rounded-2xl p-5 {{ $decConf[0] }}">
            <p class="font-bold text-lg {{ $decConf[1] }} mb-2">{{ $decConf[2] }}</p>
            @if($application->loanDecision->approved_amount)
                <p class="text-sm text-gray-600">Jumlah Disetujui: <span class="font-bold text-green-700 text-base">Rp {{ number_format($application->loanDecision->approved_amount) }}</span></p>
            @endif
            @if($application->loanDecision->conditions)
                <p class="text-sm text-gray-600 mt-1">Kondisi: {{ implode(', ', $application->loanDecision->conditions) }}</p>
            @endif
            @if($application->loanDecision->decline_reason)
                <p class="text-sm text-gray-600 mt-1">Alasan: {{ $application->loanDecision->decline_reason }}</p>
            @endif
        </div>
        @endif

        {{-- LLM Insight --}}
        @if($application->llmInsight)
        <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-6 text-white shadow-lg">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-7 h-7 bg-blue-500 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                </div>
                <p class="font-semibold">Analisis CrediSense AI</p>
                <span class="ml-auto text-xs text-slate-400">{{ $application->llmInsight->model_used }}</span>
            </div>

            <div class="space-y-3">
                <div class="bg-white/10 rounded-xl p-4">
                    <p class="text-xs text-blue-300 font-semibold mb-1 uppercase tracking-wide">Ringkasan</p>
                    <p class="text-sm text-slate-200 leading-relaxed">{{ $application->llmInsight->summary }}</p>
                </div>
                @if($application->llmInsight->approval_reasons)
                <div class="bg-green-500/20 rounded-xl p-4">
                    <p class="text-xs text-green-300 font-semibold mb-1 uppercase tracking-wide">Alasan Disetujui</p>
                    <p class="text-sm text-slate-200 leading-relaxed">{{ $application->llmInsight->approval_reasons }}</p>
                </div>
                @endif
                @if($application->llmInsight->risk_notes)
                <div class="bg-amber-500/20 rounded-xl p-4">
                    <p class="text-xs text-amber-300 font-semibold mb-1 uppercase tracking-wide">Catatan Risiko</p>
                    <p class="text-sm text-slate-200 leading-relaxed">{{ $application->llmInsight->risk_notes }}</p>
                </div>
                @endif
                @if($application->llmInsight->recommendations)
                <div class="bg-purple-500/20 rounded-xl p-4">
                    <p class="text-xs text-purple-300 font-semibold mb-1 uppercase tracking-wide">Rekomendasi</p>
                    <p class="text-sm text-slate-200 leading-relaxed">{{ $application->llmInsight->recommendations }}</p>
                </div>
                @endif
            </div>

            {{-- Payment plan --}}
            @if($application->llmInsight->payment_plan)
            <div class="mt-4 pt-4 border-t border-white/10">
                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-3">Estimasi Cicilan (Anuitas)</p>
                <div class="grid grid-cols-3 gap-3">
                    @foreach($application->llmInsight->payment_plan as $plan)
                    <div class="bg-white/10 rounded-xl p-3 text-center">
                        <p class="text-xs text-slate-400 mb-1">{{ $plan['tenor_months'] }} bulan</p>
                        <p class="font-bold text-white text-sm">Rp {{ number_format($plan['monthly_payment'],0,',','.') }}</p>
                        <p class="text-xs text-slate-400">/bulan</p>
                        <p class="text-xs text-red-300 mt-1">+Rp {{ number_format($plan['total_interest'],0,',','.') }} bunga</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @elseif($application->status === 'approved')
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5 text-center">
            <div class="animate-spin w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full mx-auto mb-2"></div>
            <p class="text-sm text-blue-700 font-medium">Analisis AI sedang dibuat...</p>
            <p class="text-xs text-blue-500 mt-0.5">Refresh halaman dalam beberapa detik</p>
        </div>
        @endif

    </div>
</x-app-layout>
