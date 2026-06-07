<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-lg text-gray-800">Dompet Saya</h2>
    </x-slot>

    <div class="p-6 space-y-6 max-w-4xl">

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center gap-2 text-sm">
                <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Balance card --}}
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-6 text-white shadow-lg">
            <p class="text-blue-200 text-sm font-medium mb-1">Saldo Tersedia</p>
            <p class="text-3xl font-bold">Rp {{ number_format($user->balance, 0, ',', '.') }}</p>
            <p class="text-blue-200 text-xs mt-2">Saldo bertambah otomatis saat pengajuan pinjaman Anda disetujui oleh AI.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Withdraw / transfer form --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-1">Transfer ke Rekening</h3>
                <p class="text-xs text-gray-400 mb-5">Demo simulasi pencairan dana dari Dompet ke rekening bank tujuan.</p>

                <form method="POST" action="{{ route('borrower.wallet.withdraw') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="bank_name" value="Nama Bank" />
                        <x-text-input id="bank_name" name="bank_name" type="text" class="block w-full mt-1"
                            value="{{ old('bank_name') }}" placeholder="Bank Central Asia" required />
                        <x-input-error :messages="$errors->get('bank_name')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="account_number" value="Nomor Rekening" />
                        <x-text-input id="account_number" name="account_number" type="text" class="block w-full mt-1"
                            value="{{ old('account_number') }}" placeholder="1234567890" required />
                        <x-input-error :messages="$errors->get('account_number')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="amount" value="Jumlah Transfer" />
                        <div class="relative mt-1">
                            <span class="absolute left-3 top-2.5 text-sm text-gray-400">Rp</span>
                            <x-text-input id="amount" name="amount" type="number" class="block w-full pl-9"
                                value="{{ old('amount') }}" min="10000" max="{{ (int) $user->balance }}" placeholder="500.000" required />
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Maksimal Rp {{ number_format($user->balance, 0, ',', '.') }} (saldo Anda saat ini)</p>
                        <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                    </div>

                    <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold px-6 py-2.5 rounded-xl transition shadow-md shadow-blue-600/20"
                            {{ $user->balance <= 0 ? 'disabled' : '' }}>
                        Kirim Transfer
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                    @if($user->balance <= 0)
                        <p class="text-xs text-amber-600 text-center">Saldo Anda masih kosong. Ajukan dan tunggu pinjaman disetujui untuk mengisi Dompet.</p>
                    @endif
                </form>
            </div>

            {{-- Transaction history --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-slate-50 px-5 py-3 border-b border-gray-100">
                    <p class="font-semibold text-gray-700 text-sm">Riwayat Mutasi</p>
                </div>

                @if($transactions->isEmpty())
                    <div class="p-8 text-center">
                        <p class="text-sm text-gray-400">Belum ada mutasi pada Dompet Anda.</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-50 max-h-[420px] overflow-y-auto">
                        @foreach($transactions as $trx)
                        <div class="px-5 py-4 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0
                                        {{ $trx->type === 'credit' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
                                @if($trx->type === 'credit')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m0 0l-6-6m6 6l6-6"/></svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20V4m0 0l-6 6m6-6l6 6"/></svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ $trx->description }}</p>
                                <p class="text-xs text-gray-400">{{ $trx->created_at->format('d M Y, H:i') }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-bold {{ $trx->type === 'credit' ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $trx->type === 'credit' ? '+' : '-' }}Rp {{ number_format($trx->amount, 0, ',', '.') }}
                                </p>
                                <p class="text-xs text-gray-400">Saldo: Rp {{ number_format($trx->balance_after, 0, ',', '.') }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
