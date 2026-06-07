<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.applications.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h2 class="font-bold text-lg text-gray-800">Detail Pengajuan <span class="text-blue-600">#{{ $application->id }}</span></h2>
                <p class="text-xs text-gray-400">{{ $application->user->name }} · {{ $application->created_at->format('d F Y') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="p-6 space-y-5 max-w-5xl">

        {{-- Top row: applicant + loan info --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow">
                        {{ strtoupper(substr($application->user->name,0,1)) }}
                    </div>
                    <div>
                        <p class="font-bold text-gray-800">{{ $application->user->name }}</p>
                        <p class="text-sm text-gray-400">{{ $application->user->email }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    @foreach([
                        ['Usia',          $application->person_age.' tahun'],
                        ['Pendapatan',    'Rp '.number_format($application->person_income)],
                        ['Kepemilikan',   $application->person_home_ownership],
                        ['Lama Kerja',    ($application->person_emp_length ?? 'N/A').' thn'],
                        ['Default SLIK',  $application->cb_person_default_on_file === 'Y' ? '⚠ Pernah' : '✓ Bersih'],
                        ['Histori Kredit',$application->cb_person_cred_hist_length.' tahun'],
                    ] as [$l,$v])
                    <div><p class="text-xs text-gray-400">{{ $l }}</p><p class="font-medium text-gray-700">{{ $v }}</p></div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="font-semibold text-gray-700 mb-4">Detail Pinjaman</p>
                @php $product = config('loan_products.'.$application->loan_product); @endphp
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-500">Tujuan</span>
                        <span class="text-sm font-semibold text-gray-800">{{ $application->loan_intent }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-500">Jumlah Diajukan</span>
                        <span class="text-sm font-bold text-gray-800">Rp {{ number_format($application->loan_amnt) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-500">Produk Kredit</span>
                        <span class="text-sm font-semibold text-gray-800">{{ $product['name'] ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-500">Suku Bunga</span>
                        <span class="text-sm font-semibold text-gray-800">{{ $application->loan_int_rate }}%/thn</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-500">Tenor Cicilan</span>
                        <span class="text-sm font-semibold text-gray-800">{{ $application->loan_tenor_months ? $application->loan_tenor_months.' bulan' : '—' }}</span>
                    </div>
                    @if($application->loan_percent_income)
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-500">Rasio Pinjaman/Pendapatan</span>
                        @php $lpi = $application->loan_percent_income*100; @endphp
                        <span class="text-sm font-semibold {{ $lpi > 50 ? 'text-red-600' : ($lpi > 30 ? 'text-amber-600' : 'text-green-600') }}">
                            {{ number_format($lpi,1) }}%
                        </span>
                    </div>
                    @endif
                    @if($application->loan_grade)
                    <div class="flex justify-between items-center py-2">
                        <span class="text-sm text-gray-500">Peringkat Kredit (Auto)</span>
                        <span class="text-xl font-black text-blue-700">Grade {{ $application->loan_grade }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Scoring + Decision row --}}
        @if($application->scoringResult)
        @php $sc = $application->scoringResult->risk_score; $color = $sc < 0.3 ? '#22c55e' : ($sc < 0.6 ? '#f59e0b' : '#ef4444'); @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="font-semibold text-gray-700 mb-4">Skor Risiko AI</p>
                <div class="flex items-center gap-5">
                    <div class="relative w-20 h-20 shrink-0">
                        @php $dash = 2*pi()*34; $filled = $dash*(1-$sc); @endphp
                        <svg class="w-20 h-20 -rotate-90" viewBox="0 0 72 72">
                            <circle cx="36" cy="36" r="34" fill="none" stroke="#f1f5f9" stroke-width="7"/>
                            <circle cx="36" cy="36" r="34" fill="none" stroke="{{ $color }}" stroke-width="7"
                                stroke-dasharray="{{ $dash }}" stroke-dashoffset="{{ $filled }}" stroke-linecap="round"/>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="font-bold text-base" style="color:{{ $color }}">{{ number_format($sc*100,1) }}%</span>
                        </div>
                    </div>
                    <div>
                        @php $catColor=['LOW'=>'text-green-600','MEDIUM'=>'text-amber-600','HIGH'=>'text-orange-600','VERY_HIGH'=>'text-red-600'][$application->scoringResult->risk_category]??'text-gray-600'; @endphp
                        <p class="font-bold text-xl {{ $catColor }}">{{ $application->scoringResult->risk_category }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $application->scoringResult->model_used }}</p>
                        @if($application->scoringResult->feature_importances)
                        <div class="mt-3 space-y-1.5">
                            @foreach(array_slice($application->scoringResult->feature_importances, 0, 4, true) as $feat => $val)
                            <div class="flex items-center gap-2 text-xs">
                                <span class="text-gray-500 w-32 truncate">{{ $feat }}</span>
                                <div class="flex-1 bg-gray-100 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full bg-blue-500" style="width:{{ min(100,$val*500) }}%"></div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($application->loanDecision)
            @php $dc=['APPROVED'=>['from-green-500 to-emerald-600','✓ DISETUJUI'],'DECLINED'=>['from-red-500 to-rose-600','✗ DITOLAK'],'REVIEW'=>['from-orange-500 to-amber-600','⚠ REVIEW']][$application->loanDecision->decision]??['from-gray-500 to-gray-600','—']; @endphp
            <div class="bg-gradient-to-br {{ $dc[0] }} rounded-2xl p-5 text-white shadow-md">
                <p class="text-white/70 text-xs font-semibold uppercase tracking-wide mb-1">Keputusan Kredit</p>
                <p class="font-bold text-2xl mb-3">{{ $dc[1] }}</p>
                @if($application->loanDecision->approved_amount)
                    <div class="bg-white/20 rounded-xl p-3">
                        <p class="text-white/70 text-xs">Jumlah Disetujui</p>
                        <p class="font-bold text-xl">Rp {{ number_format($application->loanDecision->approved_amount) }}</p>
                    </div>
                @endif
                @if($application->loanDecision->conditions)
                    <p class="text-white/80 text-sm mt-3">Kondisi: {{ $application->loanDecision->conditions }}</p>
                @endif
                @if($application->loanDecision->decline_reason)
                    <p class="text-white/80 text-sm mt-3">Alasan: {{ $application->loanDecision->decline_reason }}</p>
                @endif
            </div>
            @endif
        </div>
        @endif

        {{-- LLM Insight --}}
        @if($application->llmInsight)
        <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-6 text-white shadow-lg">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-7 h-7 bg-blue-500 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                </div>
                <p class="font-semibold">AI Insight</p>
                <span class="ml-auto text-xs text-slate-400 font-mono">{{ $application->llmInsight->model_used }}</span>
                @if($application->llmInsight->tokens_used)
                    <span class="text-xs text-slate-500">{{ $application->llmInsight->tokens_used }} tokens</span>
                @endif
            </div>
            <div class="grid md:grid-cols-2 gap-3">
                @foreach([['Ringkasan','summary','blue'],['Alasan Disetujui','approval_reasons','green'],['Catatan Risiko','risk_notes','amber'],['Rekomendasi','recommendations','purple']] as [$title,$field,$c])
                @if($application->llmInsight->$field)
                <div class="bg-white/10 rounded-xl p-4">
                    <p class="text-xs text-{{ $c }}-300 font-semibold uppercase tracking-wide mb-1.5">{{ $title }}</p>
                    <p class="text-sm text-slate-200 leading-relaxed">{{ $application->llmInsight->$field }}</p>
                </div>
                @endif
                @endforeach
            </div>
        </div>
        @endif

        {{-- Audit log --}}
        @if($application->auditLogs->isNotEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                Audit Log
            </p>
            <div class="space-y-2">
                @foreach($application->auditLogs as $log)
                <div class="flex items-center gap-3 text-xs py-2 border-b border-gray-50 last:border-0">
                    <span class="text-gray-400 w-32 shrink-0">{{ $log->created_at->format('d/m/Y H:i') }}</span>
                    <span class="font-mono bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-xs">{{ $log->action }}</span>
                    <span class="text-gray-400">{{ $log->actor_role }} · {{ $log->user?->name ?? 'system' }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</x-app-layout>
