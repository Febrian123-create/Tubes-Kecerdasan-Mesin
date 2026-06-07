<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('borrower.applications.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h2 class="font-bold text-xl text-gray-800">Ajukan Pinjaman Baru</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Progress indicator --}}
            <div class="flex items-center justify-center mb-8">
                @foreach([['1', 'Data Diri', true], ['2', 'Detail Pinjaman', true], ['3', 'Verifikasi Admin', false]] as $i => [$num, $label, $active])
                    <div class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                        <div class="flex flex-col items-center gap-1.5 shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                                        {{ $active ? 'bg-gradient-to-br from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-600/20' : 'bg-gray-100 text-gray-400 border border-gray-200' }}">
                                {{ $num }}
                            </div>
                            <span class="text-xs font-medium {{ $active ? 'text-gray-700' : 'text-gray-400' }} whitespace-nowrap">{{ $label }}</span>
                        </div>
                        @if(!$loop->last)
                            <div class="flex-1 h-px mx-3 mb-5 {{ $active ? 'bg-gradient-to-r from-blue-600 to-indigo-200' : 'bg-gray-200' }}"></div>
                        @endif
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('borrower.applications.store') }}">
                @csrf

                {{-- Section 1: Data Diri --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-5">
                    <h3 class="font-semibold text-gray-800 mb-5 flex items-center gap-2">
                        <span class="bg-blue-100 text-blue-700 rounded-lg p-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </span>
                        Data Diri
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <x-input-label for="person_age" value="Usia" />
                            <div class="relative mt-1">
                                <x-text-input id="person_age" name="person_age" type="number" class="block w-full pr-12"
                                    value="{{ old('person_age') }}" min="18" max="100" placeholder="25" required />
                                <span class="absolute right-3 top-2.5 text-sm text-gray-400">tahun</span>
                            </div>
                            <x-input-error :messages="$errors->get('person_age')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="person_income" value="Pendapatan Tahunan" />
                            <div class="relative mt-1">
                                <span class="absolute left-3 top-2.5 text-sm text-gray-400">Rp</span>
                                <x-text-input id="person_income" name="person_income" type="number" class="block w-full pl-9"
                                    value="{{ old('person_income') }}" min="1" placeholder="60.000.000" required />
                            </div>
                            <x-input-error :messages="$errors->get('person_income')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="person_home_ownership" value="Status Kepemilikan Rumah" />
                            <x-select id="person_home_ownership" name="person_home_ownership" class="mt-1 block w-full">
                                @foreach(['RENT'=>'Sewa (Kost/Kontrak)','OWN'=>'Milik Sendiri','MORTGAGE'=>'Cicil KPR','OTHER'=>'Lainnya'] as $v => $l)
                                    <option value="{{ $v }}" {{ old('person_home_ownership') === $v ? 'selected' : '' }}>{{ $l }}</option>
                                @endforeach
                            </x-select>
                            <x-input-error :messages="$errors->get('person_home_ownership')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="person_emp_length" value="Lama Bekerja (opsional)" />
                            <div class="relative mt-1">
                                <x-text-input id="person_emp_length" name="person_emp_length" type="number" class="block w-full pr-12"
                                    value="{{ old('person_emp_length') }}" min="0" max="60" step="0.5" placeholder="3" />
                                <span class="absolute right-3 top-2.5 text-sm text-gray-400">tahun</span>
                            </div>
                            <x-input-error :messages="$errors->get('person_emp_length')" class="mt-1" />
                        </div>
                    </div>
                </div>

                {{-- Section 2: Detail Pinjaman --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-5"
                     x-data="{
                        products: @js($products),
                        selected: @js(old('loan_product')),
                        tenor: @js(old('loan_tenor_months') ? (int) old('loan_tenor_months') : null),
                        amount: @js(old('loan_amnt')),
                        get product() { return this.selected ? this.products[this.selected] : null },
                        selectProduct(key) { this.selected = key; this.tenor = null },
                        fmt(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID') },
                        monthlyPayment() {
                            if (!this.product || !this.tenor || !this.amount) return null;
                            const P = parseFloat(this.amount);
                            if (!P) return null;
                            const r = this.product.rate / 100 / 12;
                            const n = this.tenor;
                            return r === 0 ? P / n : P * (r * Math.pow(1+r, n)) / (Math.pow(1+r, n) - 1);
                        },
                     }">
                    <h3 class="font-semibold text-gray-800 mb-5 flex items-center gap-2">
                        <span class="bg-green-100 text-green-700 rounded-lg p-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        Detail Pinjaman
                    </h3>

                    <div class="mb-5">
                        <x-input-label for="loan_intent" value="Tujuan Penggunaan Dana" />
                        <x-select id="loan_intent" name="loan_intent" class="mt-1 block w-full">
                            @foreach([
                                'PERSONAL'          => 'Kebutuhan Pribadi',
                                'EDUCATION'         => 'Pendidikan',
                                'MEDICAL'           => 'Medis / Kesehatan',
                                'VENTURE'           => 'Usaha / Bisnis',
                                'HOMEIMPROVEMENT'   => 'Renovasi Rumah',
                                'DEBTCONSOLIDATION' => 'Konsolidasi Hutang',
                            ] as $v => $l)
                                <option value="{{ $v }}" {{ old('loan_intent') === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </x-select>
                        <x-input-error :messages="$errors->get('loan_intent')" class="mt-1" />
                    </div>

                    {{-- Pilih Produk Kredit --}}
                    <div class="mb-5">
                        <x-input-label value="Pilih Produk Kredit" />
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-2">
                            <template x-for="[key, p] in Object.entries(products)" :key="key">
                                <button type="button" @click="selectProduct(key)"
                                        class="text-left rounded-xl border p-4 transition"
                                        :class="selected === key ? 'border-blue-600 bg-blue-50/60 ring-2 ring-blue-100' : 'border-gray-200 hover:border-gray-300'">
                                    <p class="font-semibold text-gray-800 text-sm" x-text="p.name"></p>
                                    <p class="text-xs text-gray-400 mt-0.5 leading-relaxed" x-text="p.desc"></p>
                                    <div class="mt-3 flex items-baseline gap-1">
                                        <span class="font-bold text-blue-700 text-lg" x-text="p.rate.toFixed(1) + '%'"></span>
                                        <span class="text-xs text-gray-400">/tahun</span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Limit s.d. <span class="font-medium text-gray-700" x-text="fmt(p.limit)"></span></p>
                                </button>
                            </template>
                        </div>
                        <input type="hidden" name="loan_product" :value="selected">
                        <input type="hidden" name="loan_int_rate" :value="product ? product.rate : ''">
                        <x-input-error :messages="$errors->get('loan_product')" class="mt-1" />
                    </div>

                    {{-- Jangka Waktu Cicilan --}}
                    <div class="mb-5" x-show="product" x-transition.duration.200ms>
                        <x-input-label value="Jangka Waktu Cicilan" />
                        <div class="flex flex-wrap gap-2 mt-2">
                            <template x-for="t in (product ? product.tenors : [])" :key="t">
                                <button type="button" @click="tenor = t"
                                        class="px-4 py-2 rounded-xl text-sm font-medium border transition"
                                        :class="tenor === t ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white border-transparent shadow-md shadow-blue-600/20' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                                        x-text="t + ' bulan'">
                                </button>
                            </template>
                        </div>
                        <input type="hidden" name="loan_tenor_months" :value="tenor">
                        <x-input-error :messages="$errors->get('loan_tenor_months')" class="mt-1" />
                    </div>

                    {{-- Jumlah Pinjaman --}}
                    <div>
                        <x-input-label for="loan_amnt" value="Jumlah Pinjaman" />
                        <div class="relative mt-1">
                            <span class="absolute left-3 top-2.5 text-sm text-gray-400">Rp</span>
                            <x-text-input id="loan_amnt" name="loan_amnt" type="number" class="block w-full pl-9"
                                x-model="amount" min="500" placeholder="10.000.000" required />
                        </div>
                        <p class="text-xs text-gray-400 mt-1" x-show="product" x-text="product ? ('Maksimal ' + fmt(product.limit) + ' untuk produk ini') : ''"></p>
                        <x-input-error :messages="$errors->get('loan_amnt')" class="mt-1" />
                    </div>

                    {{-- Live cicilan estimasi --}}
                    <div class="mt-5 p-4 bg-blue-50 rounded-xl" x-show="product && tenor && amount && monthlyPayment()" x-transition.duration.200ms>
                        <p class="text-xs text-blue-600 font-semibold mb-2">Estimasi Cicilan (Anuitas)</p>
                        <div class="flex items-baseline gap-1.5">
                            <span class="font-bold text-blue-700 text-xl" x-text="monthlyPayment() ? fmt(monthlyPayment()) : '—'"></span>
                            <span class="text-xs text-gray-500">/bulan selama <span x-text="tenor"></span> bulan</span>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">*Estimasi sebelum verifikasi. Jumlah final ditentukan setelah penilaian kredit.</p>
                    </div>
                </div>

                {{-- Info box --}}
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 flex gap-3">
                    <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm text-amber-700">Riwayat kredit Anda akan diverifikasi oleh admin melalui <strong>SLIK OJK</strong> sebelum penilaian AI dijalankan.</p>
                </div>

                <div class="flex justify-between items-center">
                    <a href="{{ route('borrower.applications.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">Batal</a>
                    <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold px-8 py-2.5 rounded-xl transition shadow-md shadow-blue-600/20">
                        Kirim Pengajuan
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
