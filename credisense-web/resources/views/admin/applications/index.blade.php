<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-lg text-gray-800">Semua Pengajuan Pinjaman</h2>
    </x-slot>

    <div class="p-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-slate-50 border-b border-gray-100">
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Peminjam</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pinjaman</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Skor Risiko</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Keputusan</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-5 py-3.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($applications as $app)
                        @php
                            $cfg = [
                                'pending'  => ['bg-amber-100 text-amber-700',  'Pending'],
                                'scored'   => ['bg-purple-100 text-purple-700','Scored'],
                                'approved' => ['bg-green-100 text-green-700',  'Approved'],
                                'rejected' => ['bg-red-100 text-red-700',      'Rejected'],
                                'review'   => ['bg-orange-100 text-orange-700','Review'],
                            ][$app->status] ?? ['bg-gray-100 text-gray-600', $app->status];
                        @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm shrink-0">
                                        {{ strtoupper(substr($app->user->name,0,1)) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800 text-sm">{{ $app->user->name }}</p>
                                        <p class="text-xs text-gray-400">#{{ $app->id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-gray-800 text-sm">Rp {{ number_format($app->loan_amnt) }}</p>
                                <p class="text-xs text-gray-400">{{ $app->loan_intent }} · {{ $app->loan_int_rate }}%/thn</p>
                            </td>
                            <td class="px-5 py-4">
                                @if($app->scoringResult)
                                    @php $sc = $app->scoringResult->risk_score; @endphp
                                    <div class="flex items-center gap-2">
                                        <div class="w-20 bg-gray-100 rounded-full h-1.5">
                                            <div class="h-1.5 rounded-full {{ $sc < 0.3 ? 'bg-green-500' : ($sc < 0.6 ? 'bg-amber-500' : 'bg-red-500') }}"
                                                 style="width:{{ $sc*100 }}%"></div>
                                        </div>
                                        <span class="text-xs font-semibold {{ $sc < 0.3 ? 'text-green-600' : ($sc < 0.6 ? 'text-amber-600' : 'text-red-600') }}">
                                            {{ number_format($sc*100,1) }}%
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $app->scoringResult->risk_category }}</p>
                                @else
                                    <span class="text-gray-300 text-sm">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if($app->loanDecision)
                                    @php $dc = ['APPROVED'=>'text-green-700 font-bold','DECLINED'=>'text-red-700 font-bold','REVIEW'=>'text-orange-700 font-semibold']; @endphp
                                    <p class="text-sm {{ $dc[$app->loanDecision->decision] ?? '' }}">{{ $app->loanDecision->decision }}</p>
                                    @if($app->loanDecision->approved_amount)
                                        <p class="text-xs text-gray-400">Rp {{ number_format($app->loanDecision->approved_amount) }}</p>
                                    @endif
                                @else
                                    <span class="text-gray-300 text-sm">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $cfg[0] }}">{{ $cfg[1] }}</span>
                            </td>
                            <td class="px-5 py-4 text-xs text-gray-400 whitespace-nowrap">{{ $app->created_at->format('d M Y') }}</td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('admin.applications.show', $app) }}"
                                   class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs px-3 py-1.5 rounded-lg transition">
                                    Detail
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-gray-100">{{ $applications->links() }}</div>
        </div>
    </div>
</x-app-layout>
