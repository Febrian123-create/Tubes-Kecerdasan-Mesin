<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.applications.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h2 class="font-bold text-xl text-gray-800">Verifikasi SLIK OJK</h2>
                <p class="text-sm text-gray-500">Pengajuan #{{ $application->id }} — {{ $application->user->name }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            {{-- Applicant summary cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach([
                    ['Peminjam',   $application->user->name,                         'bg-white'],
                    ['Tujuan',     $application->loan_intent,                         'bg-white'],
                    ['Jumlah',     'Rp '.number_format($application->loan_amnt),      'bg-white'],
                    ['Pendapatan', 'Rp '.number_format($application->person_income),  'bg-white'],
                ] as [$label, $value, $bg])
                <div class="{{ $bg }} rounded-xl border border-gray-100 shadow-sm p-4">
                    <p class="text-xs text-gray-400 font-medium mb-1">{{ $label }}</p>
                    <p class="font-semibold text-gray-800 text-sm truncate">{{ $value }}</p>
                </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                    <p class="text-xs text-gray-400 font-medium mb-1">Suku Bunga</p>
                    <p class="font-bold text-gray-800">{{ $application->loan_int_rate }}%<span class="text-xs text-gray-400 font-normal">/tahun</span></p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                    <p class="text-xs text-gray-400 font-medium mb-1">Peringkat Kredit (Auto)</p>
                    @php
                        $thresholds = [9.24=>1,12.24=>2,14.40=>3,16.07=>4,17.68=>5,19.35=>6];
                        $grade = 7;
                        foreach($thresholds as $t=>$g) { if($application->loan_int_rate < $t) { $grade=$g; break; } }
                        $gradeLabels = [1=>'A',2=>'B',3=>'C',4=>'D',5=>'E',6=>'F',7=>'G'];
                    @endphp
                    <p class="font-bold text-blue-700 text-xl">Grade {{ $gradeLabels[$grade] }}</p>
                    <p class="text-xs text-gray-400">Diturunkan otomatis dari suku bunga</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                    <p class="text-xs text-gray-400 font-medium mb-1">Rasio Pinjaman/Pendapatan</p>
                    @php $lpi = $application->person_income > 0 ? round($application->loan_amnt/$application->person_income*100,1) : 0; @endphp
                    <p class="font-bold {{ $lpi > 50 ? 'text-red-600' : ($lpi > 30 ? 'text-yellow-600' : 'text-green-600') }} text-xl">{{ $lpi }}%</p>
                    <p class="text-xs text-gray-400">{{ $lpi > 50 ? 'Terlalu tinggi' : ($lpi > 30 ? 'Perlu perhatian' : 'Aman') }}</p>
                </div>
            </div>

            {{-- SLIK Form --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-1 flex items-center gap-2">
                    <span class="bg-orange-100 text-orange-700 rounded-lg p-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </span>
                    Data dari SLIK OJK
                </h3>
                <p class="text-sm text-gray-500 mb-5">Isi berdasarkan hasil pengecekan Sistem Layanan Informasi Keuangan (SLIK) OJK.</p>

                <form method="POST" action="{{ route('admin.applications.enrich.update', $application) }}" class="space-y-5">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <x-input-label for="cb_person_default_on_file" value="Riwayat Gagal Bayar (SLIK)" />
                            <x-select id="cb_person_default_on_file" name="cb_person_default_on_file" class="mt-1 block w-full">
                                <option value="N" {{ old('cb_person_default_on_file', 'N') === 'N' ? 'selected' : '' }}>
                                    Tidak ada catatan gagal bayar
                                </option>
                                <option value="Y" {{ old('cb_person_default_on_file') === 'Y' ? 'selected' : '' }}>
                                    Ada catatan gagal bayar
                                </option>
                            </x-select>
                            <x-input-error :messages="$errors->get('cb_person_default_on_file')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="cb_person_cred_hist_length" value="Panjang Histori Kredit" />
                            <div class="relative mt-1">
                                <x-text-input id="cb_person_cred_hist_length" name="cb_person_cred_hist_length"
                                    type="number" class="block w-full pr-14"
                                    value="{{ old('cb_person_cred_hist_length', 0) }}" min="0" max="50" required />
                                <span class="absolute right-3 top-2.5 text-sm text-gray-400">tahun</span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Berapa lama peminjam punya riwayat kredit aktif</p>
                            <x-input-error :messages="$errors->get('cb_person_cred_hist_length')" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="notes" value="Catatan Admin (opsional)" />
                        <textarea id="notes" name="notes" rows="2"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                            placeholder="Catatan tambahan dari hasil pengecekan SLIK atau observasi admin...">{{ old('notes') }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3">
                        <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm text-amber-700">Setelah disimpan, sistem AI akan otomatis menjalankan <strong>penilaian risiko</strong> dan <strong>keputusan kredit</strong> di background.</p>
                    </div>

                    <div class="flex justify-between items-center pt-2">
                        <a href="{{ route('admin.applications.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">Batal</a>
                        <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold px-8 py-2.5 rounded-xl transition shadow-md shadow-blue-600/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Simpan & Jalankan Scoring AI
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
