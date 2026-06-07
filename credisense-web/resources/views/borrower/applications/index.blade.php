<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-lg text-gray-800">Dashboard Peminjam</h2>
    </x-slot>

    <div class="p-6 space-y-6">

        {{-- Hero gradient card --}}
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-6 text-white shadow-lg">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <p class="text-blue-200 text-sm font-medium mb-1">Selamat datang kembali</p>
                    <h3 class="text-2xl font-bold">{{ auth()->user()->name }}</h3>
                    <p class="text-blue-200 text-sm mt-1">Pantau dan kelola pengajuan pinjaman Anda</p>
                </div>
                <div class="flex flex-col sm:items-end gap-2 shrink-0">
                    <a href="{{ route('borrower.wallet.index') }}" class="text-right group">
                        <p class="text-blue-200 text-xs">Saldo Dompet</p>
                        <p class="text-xl font-bold group-hover:underline">Rp {{ number_format(auth()->user()->balance, 0, ',', '.') }}</p>
                    </a>
                    <a href="{{ route('borrower.applications.create') }}"
                       class="inline-flex items-center gap-2 bg-white text-blue-700 font-semibold px-5 py-2.5 rounded-xl hover:bg-blue-50 transition shadow-md text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Ajukan Pinjaman Baru
                    </a>
                </div>
            </div>

            {{-- Mini stats --}}
            @php
                $total    = $applications->count();
                $approved = $applications->where('status','approved')->count();
                $pending  = $applications->whereIn('status',['pending','enriched','scored'])->count();
            @endphp
            <div class="grid grid-cols-3 gap-3 mt-5 pt-5 border-t border-blue-500/40">
                <div class="text-center"><p class="text-2xl font-bold">{{ $total }}</p><p class="text-blue-200 text-xs">Total</p></div>
                <div class="text-center"><p class="text-2xl font-bold text-green-300">{{ $approved }}</p><p class="text-blue-200 text-xs">Disetujui</p></div>
                <div class="text-center"><p class="text-2xl font-bold text-yellow-300">{{ $pending }}</p><p class="text-blue-200 text-xs">Diproses</p></div>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center gap-2 text-sm">
                <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Applications list --}}
        <div>
            <h3 class="font-semibold text-gray-700 mb-3">Riwayat Pengajuan</h3>

            @if($applications->isEmpty())
                <div class="bg-white rounded-2xl border border-dashed border-gray-200 p-12 text-center">
                    <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <p class="font-semibold text-gray-600 mb-1">Belum ada pengajuan</p>
                    <p class="text-gray-400 text-sm">Mulai ajukan pinjaman dan dapatkan analisis kredit berbasis AI</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($applications as $app)
                    @php
                        $cfg = [
                            'pending'  => ['left-bar'=>'bg-amber-400',  'badge'=>'bg-amber-100 text-amber-700',  'label'=>'Menunggu Verifikasi'],
                            'enriched' => ['left-bar'=>'bg-blue-400',   'badge'=>'bg-blue-100 text-blue-700',    'label'=>'Sedang Diproses'],
                            'scored'   => ['left-bar'=>'bg-purple-400', 'badge'=>'bg-purple-100 text-purple-700','label'=>'Menunggu Keputusan'],
                            'approved' => ['left-bar'=>'bg-green-500',  'badge'=>'bg-green-100 text-green-700',  'label'=>'Disetujui ✓'],
                            'rejected' => ['left-bar'=>'bg-red-500',    'badge'=>'bg-red-100 text-red-700',      'label'=>'Ditolak'],
                            'review'   => ['left-bar'=>'bg-orange-400', 'badge'=>'bg-orange-100 text-orange-700','label'=>'Perlu Tinjauan'],
                        ][$app->status] ?? ['left-bar'=>'bg-gray-300','badge'=>'bg-gray-100 text-gray-600','label'=>$app->status];
                    @endphp
                    <a href="{{ route('borrower.applications.show', $app) }}"
                       class="flex bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 group">
                        <div class="w-1.5 {{ $cfg['left-bar'] }} shrink-0"></div>
                        <div class="flex-1 px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full {{ $cfg['badge'] }}">{{ $cfg['label'] }}</span>
                                    <span class="text-xs text-gray-400">#{{ $app->id }}</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                                    <span class="font-semibold text-gray-800">Rp {{ number_format($app->loan_amnt) }}</span>
                                    <span class="text-gray-500">{{ $app->loan_intent }}</span>
                                    <span class="text-gray-400">{{ $app->loan_int_rate }}%/thn</span>
                                    @if($app->scoringResult)
                                        @php $sc = $app->scoringResult->risk_score; @endphp
                                        <span class="font-medium {{ $sc < 0.3 ? 'text-green-600' : ($sc < 0.6 ? 'text-amber-600' : 'text-red-600') }}">
                                            Risiko: {{ number_format($sc*100,1) }}%
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="text-xs text-gray-400">{{ $app->created_at->format('d M Y') }}</span>
                                <svg class="w-4 h-4 text-gray-300 group-hover:text-blue-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
