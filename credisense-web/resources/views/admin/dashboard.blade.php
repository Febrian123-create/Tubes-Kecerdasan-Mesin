<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-lg text-gray-800">Dashboard Admin</h2>
    </x-slot>

    <div class="p-6 space-y-6">

        {{-- Stats grid --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            @foreach([
                ['Total',     $stats['total'],    'from-slate-600 to-slate-700',   '📋'],
                ['Pending',   $stats['pending'],  'from-amber-500 to-orange-500',  '⏳'],
                ['Disetujui', $stats['approved'], 'from-green-500 to-emerald-600', '✓'],
                ['Ditolak',   $stats['rejected'], 'from-red-500 to-rose-600',      '✗'],
                ['Review',    $stats['review'],   'from-purple-500 to-indigo-600', '👁'],
            ] as [$label, $count, $grad, $icon])
            <div class="bg-gradient-to-br {{ $grad }} rounded-2xl p-5 text-white shadow-md">
                <div class="flex items-start justify-between mb-3">
                    <span class="text-2xl">{{ $icon }}</span>
                    @if($label === 'Pending' && $count > 0)
                        <span class="w-2.5 h-2.5 bg-white rounded-full animate-pulse"></span>
                    @endif
                </div>
                <p class="text-3xl font-bold">{{ $count }}</p>
                <p class="text-white/70 text-xs mt-0.5 font-medium">{{ $label }}</p>
            </div>
            @endforeach
        </div>

        {{-- Recent applications --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <h3 class="font-semibold text-gray-800">Pengajuan Terbaru</h3>
                    @if($stats['pending'] > 0)
                        <span class="bg-amber-100 text-amber-700 text-xs font-semibold px-2.5 py-1 rounded-full animate-pulse">
                            {{ $stats['pending'] }} perlu tindakan
                        </span>
                    @endif
                </div>
                <a href="{{ route('admin.applications.index') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Lihat Semua →</a>
            </div>

            <div class="divide-y divide-gray-50">
                @foreach($recent as $app)
                @php
                    $cfg = [
                        'pending'  => ['dot'=>'bg-amber-400',  'badge'=>'bg-amber-100 text-amber-700',  'label'=>'Pending'],
                        'enriched' => ['dot'=>'bg-blue-400',   'badge'=>'bg-blue-100 text-blue-700',    'label'=>'Diproses'],
                        'scored'   => ['dot'=>'bg-purple-400', 'badge'=>'bg-purple-100 text-purple-700','label'=>'Scored'],
                        'approved' => ['dot'=>'bg-green-500',  'badge'=>'bg-green-100 text-green-700',  'label'=>'Approved'],
                        'rejected' => ['dot'=>'bg-red-500',    'badge'=>'bg-red-100 text-red-700',      'label'=>'Rejected'],
                        'review'   => ['dot'=>'bg-orange-400', 'badge'=>'bg-orange-100 text-orange-700','label'=>'Review'],
                    ][$app->status] ?? ['dot'=>'bg-gray-300','badge'=>'bg-gray-100 text-gray-600','label'=>$app->status];
                @endphp
                <div class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50 transition">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm shrink-0 shadow-sm">
                        {{ strtoupper(substr($app->user->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="font-semibold text-gray-800 text-sm truncate">{{ $app->user->name }}</span>
                            <span class="text-xs text-gray-400">#{{ $app->id }}</span>
                            <span class="w-1.5 h-1.5 rounded-full {{ $cfg['dot'] }} shrink-0"></span>
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $cfg['badge'] }}">{{ $cfg['label'] }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-gray-500 flex-wrap">
                            <span class="font-semibold text-gray-700">Rp {{ number_format($app->loan_amnt) }}</span>
                            <span>{{ $app->loan_intent }}</span>
                            @if($app->scoringResult)
                                @php $sc = $app->scoringResult->risk_score; @endphp
                                <span class="font-semibold {{ $sc < 0.3 ? 'text-green-600' : ($sc < 0.6 ? 'text-amber-600' : 'text-red-600') }}">
                                    {{ number_format($sc*100,1) }}% risiko
                                </span>
                            @endif
                            <span class="text-gray-400">{{ $app->created_at->format('d M Y') }}</span>
                        </div>
                    </div>
                    @if($app->status === 'pending')
                        <a href="{{ route('admin.applications.enrich', $app) }}"
                           class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3.5 py-2 rounded-xl transition shadow-sm shrink-0">
                            Verifikasi SLIK
                        </a>
                    @else
                        <a href="{{ route('admin.applications.show', $app) }}"
                           class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-medium px-3.5 py-2 rounded-xl transition shrink-0">
                            Detail
                        </a>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
