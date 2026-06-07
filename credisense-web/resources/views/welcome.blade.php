<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'CrediSense AI') }} — Analisis Kredit Berbasis AI</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white text-slate-800">

    {{-- ── NAVBAR ──────────────────────────────────────────────────────── --}}
    <header class="sticky top-0 z-30 bg-white/80 backdrop-blur border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-600/20">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div class="leading-tight">
                    <p class="font-bold text-slate-900 text-sm">CrediSense AI</p>
                    <p class="text-slate-400 text-[11px]">Credit Risk Platform</p>
                </div>
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                <a href="#fitur" class="hover:text-slate-900 transition">Fitur</a>
                <a href="#cara-kerja" class="hover:text-slate-900 transition">Cara Kerja</a>
                <a href="#faq" class="hover:text-slate-900 transition">FAQ</a>
            </nav>

            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ url('/dashboard') }}"
                       class="bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition shadow-sm">
                        Buka Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="hidden sm:inline-block text-sm font-medium text-slate-600 hover:text-slate-900 transition px-3 py-2">
                        Masuk
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                           class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition shadow-md shadow-blue-600/20">
                            Daftar Gratis
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </header>

    {{-- ── HERO ────────────────────────────────────────────────────────── --}}
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-blue-50/70 via-white to-white"></div>
        <div class="absolute -top-32 -right-32 w-[28rem] h-[28rem] bg-gradient-to-br from-blue-400/20 to-indigo-500/20 rounded-full blur-3xl"></div>
        <div class="absolute top-40 -left-40 w-[24rem] h-[24rem] bg-gradient-to-br from-indigo-300/20 to-purple-400/10 rounded-full blur-3xl"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-24 lg:pt-28 lg:pb-32">
            <div class="max-w-3xl">
                <span class="inline-flex items-center gap-2 bg-blue-50 text-blue-700 text-xs font-semibold px-3.5 py-1.5 rounded-full border border-blue-100">
                    <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-pulse"></span>
                    Didukung Machine Learning &amp; Data SLIK OJK
                </span>

                <h1 class="mt-6 text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 leading-[1.1] tracking-tight">
                    Keputusan kredit yang <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">lebih cepat, lebih akurat</span>, didukung AI
                </h1>

                <p class="mt-6 text-lg text-slate-500 leading-relaxed max-w-2xl">
                    CrediSense AI membantu lembaga pembiayaan menilai risiko kredit peminjam secara otomatis —
                    memadukan model machine learning dengan verifikasi data SLIK OJK, sehingga proses persetujuan
                    pinjaman jadi lebih cepat, transparan, dan minim risiko gagal bayar.
                </p>

                <div class="mt-9 flex flex-col sm:flex-row gap-3">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                           class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold px-7 py-3.5 rounded-xl transition shadow-lg shadow-blue-600/25">
                            Buka Dashboard
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    @else
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold px-7 py-3.5 rounded-xl transition shadow-lg shadow-blue-600/25">
                            Mulai Sekarang — Gratis
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center justify-center gap-2 bg-white hover:bg-slate-50 text-slate-700 font-semibold px-7 py-3.5 rounded-xl transition border border-slate-200 shadow-sm">
                            Saya sudah punya akun
                        </a>
                    @endauth
                </div>

                <div class="mt-12 flex flex-wrap items-center gap-x-10 gap-y-4">
                    @foreach([['< 2 menit','Waktu rata-rata penilaian risiko'],['SLIK OJK','Verifikasi data terintegrasi'],['24/7','Pemrosesan pengajuan otomatis']] as [$big,$small])
                    <div>
                        <p class="text-2xl font-extrabold text-slate-900">{{ $big }}</p>
                        <p class="text-sm text-slate-400">{{ $small }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Floating preview card --}}
            <div class="mt-16 lg:mt-[-4rem] lg:ml-auto lg:w-[34rem] relative">
                <div class="bg-white rounded-2xl shadow-2xl shadow-slate-900/10 border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm shadow-sm">AS</div>
                            <div>
                                <p class="font-semibold text-slate-800 text-sm">Andi Saputra</p>
                                <p class="text-xs text-slate-400">Pengajuan #1042 · Modal Usaha</p>
                            </div>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-green-100 text-green-700">Disetujui ✓</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-5">
                        <div class="bg-slate-50 rounded-xl p-4">
                            <p class="text-xs text-slate-400 font-medium mb-1">Jumlah Pinjaman</p>
                            <p class="font-bold text-slate-800">Rp 75.000.000</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4">
                            <p class="text-xs text-slate-400 font-medium mb-1">Skor Risiko AI</p>
                            <p class="font-bold text-green-600">12.4% — Rendah</p>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-xs text-slate-500 mb-1.5">
                            <span>Probabilitas gagal bayar</span>
                            <span class="font-semibold text-slate-700">12%</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full w-[12%] rounded-full bg-gradient-to-r from-green-400 to-emerald-500"></div>
                        </div>
                    </div>
                </div>

                <div class="hidden sm:flex absolute -bottom-6 -left-8 bg-white rounded-2xl shadow-xl shadow-slate-900/10 border border-gray-100 p-4 items-center gap-3 w-56">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Analisis instan</p>
                        <p class="text-xs text-slate-400">Skor risiko dalam hitungan detik</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── LOGOS / TRUST STRIP ─────────────────────────────────────────── --}}
    <section class="border-y border-gray-100 bg-slate-50/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-wrap items-center justify-center gap-x-12 gap-y-3 text-sm text-slate-400 font-medium">
            <span>Dipercaya oleh tim analis &amp; lembaga pembiayaan untuk:</span>
            <span class="text-slate-500">Penilaian Risiko Otomatis</span>
            <span class="text-slate-300">•</span>
            <span class="text-slate-500">Verifikasi SLIK OJK</span>
            <span class="text-slate-300">•</span>
            <span class="text-slate-500">Manajemen Pengajuan</span>
            <span class="text-slate-300">•</span>
            <span class="text-slate-500">Pelaporan Real-time</span>
        </div>
    </section>

    {{-- ── FITUR ───────────────────────────────────────────────────────── --}}
    <section id="fitur" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="max-w-2xl mx-auto text-center mb-14">
            <span class="text-blue-600 font-semibold text-sm tracking-wide uppercase">Fitur Utama</span>
            <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Semua yang dibutuhkan untuk menilai risiko kredit</h2>
            <p class="mt-4 text-slate-500">Dari pengajuan masuk hingga keputusan akhir — satu platform yang menyatukan data, model AI, dan proses verifikasi Anda.</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach([
                [
                    'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                    'grad' => 'from-blue-500 to-indigo-600',
                    'title' => 'Skor Risiko Berbasis AI',
                    'desc' => 'Model machine learning menganalisis profil dan riwayat keuangan peminjam untuk menghasilkan skor probabilitas gagal bayar secara instan.',
                ],
                [
                    'icon' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.286z',
                    'grad' => 'from-emerald-500 to-teal-600',
                    'title' => 'Verifikasi SLIK OJK',
                    'desc' => 'Tim verifikator dapat memeriksa dan melengkapi data SLIK langsung dalam alur kerja, memastikan keputusan kredit didukung data yang valid.',
                ],
                [
                    'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                    'grad' => 'from-purple-500 to-fuchsia-600',
                    'title' => 'Dashboard Real-time',
                    'desc' => 'Pantau status setiap pengajuan, distribusi keputusan, dan tren risiko portofolio dalam satu tampilan yang mudah dibaca.',
                ],
                [
                    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                    'grad' => 'from-amber-500 to-orange-600',
                    'title' => 'Manajemen Pengajuan',
                    'desc' => 'Ajukan, lacak, dan kelola pinjaman dengan alur status yang jelas — dari menunggu verifikasi hingga keputusan akhir disetujui atau ditolak.',
                ],
                [
                    'icon' => 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
                    'grad' => 'from-sky-500 to-blue-600',
                    'title' => 'Analitik &amp; Statistik',
                    'desc' => 'Lihat ringkasan performa dalam angka — total pengajuan, tingkat persetujuan, dan rata-rata skor risiko per periode.',
                ],
                [
                    'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
                    'grad' => 'from-slate-600 to-slate-800',
                    'title' => 'Aman &amp; Berbasis Peran',
                    'desc' => 'Akses dipisah antara peminjam dan administrator, sehingga setiap pengguna hanya melihat data dan aksi yang relevan dengan perannya.',
                ],
            ] as $f)
            <div class="group bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-200 p-6">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $f['grad'] }} flex items-center justify-center shadow-md mb-5">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $f['icon'] }}"/></svg>
                </div>
                <h3 class="font-bold text-slate-900 mb-2">{!! $f['title'] !!}</h3>
                <p class="text-sm text-slate-500 leading-relaxed">{!! $f['desc'] !!}</p>
            </div>
            @endforeach
        </div>
    </section>

    {{-- ── CARA KERJA ──────────────────────────────────────────────────── --}}
    <section id="cara-kerja" class="bg-slate-900 relative overflow-hidden">
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="max-w-2xl mx-auto text-center mb-16">
                <span class="text-blue-400 font-semibold text-sm tracking-wide uppercase">Cara Kerja</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-white tracking-tight">Tiga langkah menuju keputusan kredit</h2>
                <p class="mt-4 text-slate-400">Proses yang sederhana bagi peminjam, dan alur kerja yang efisien bagi tim verifikasi Anda.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                @foreach([
                    ['no'=>'01','title'=>'Ajukan Pinjaman','desc'=>'Peminjam mengisi data diri dan detail pinjaman melalui formulir terpandu — cepat dan jelas tahapannya.'],
                    ['no'=>'02','title'=>'AI Menganalisis & Tim Memverifikasi','desc'=>'Model machine learning menghitung skor risiko, sementara admin melengkapi verifikasi data SLIK OJK.'],
                    ['no'=>'03','title'=>'Keputusan Disampaikan','desc'=>'Status pengajuan diperbarui secara real-time — disetujui, ditolak, atau perlu tinjauan lebih lanjut.'],
                ] as $step)
                <div class="bg-white/5 border border-white/10 rounded-2xl p-7 backdrop-blur-sm">
                    <span class="inline-block bg-gradient-to-br from-blue-500 to-indigo-600 bg-clip-text text-transparent text-4xl font-extrabold">{{ $step['no'] }}</span>
                    <h3 class="mt-4 font-bold text-white text-lg">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-sm text-slate-400 leading-relaxed">{{ $step['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── FAQ ─────────────────────────────────────────────────────────── --}}
    <section id="faq" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="max-w-2xl mx-auto text-center mb-14">
            <span class="text-blue-600 font-semibold text-sm tracking-wide uppercase">FAQ</span>
            <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Pertanyaan yang sering diajukan</h2>
        </div>

        <div class="space-y-3" x-data="{ openIndex: 0 }">
            @foreach([
                ['q'=>'Bagaimana CrediSense AI menghitung skor risiko?','a'=>'Sistem menggunakan model machine learning yang dilatih dari data historis pinjaman — mempertimbangkan faktor seperti pendapatan, usia, status kepemilikan rumah, riwayat kerja, dan tujuan pinjaman — untuk memperkirakan probabilitas gagal bayar.'],
                ['q'=>'Apakah data SLIK OJK terintegrasi otomatis?','a'=>'Tim verifikator dapat melengkapi data SLIK langsung dari halaman verifikasi dalam aplikasi, sehingga setiap keputusan kredit selalu didukung data terverifikasi sebelum diproses lebih lanjut.'],
                ['q'=>'Siapa saja yang bisa menggunakan platform ini?','a'=>'Ada dua peran: Peminjam, yang dapat mengajukan dan memantau status pinjamannya; dan Administrator, yang memverifikasi data, meninjau skor risiko, serta mengambil keputusan akhir.'],
                ['q'=>'Apakah saya bisa melacak status pengajuan saya?','a'=>'Tentu — setiap peminjam memiliki dashboard pribadi yang menampilkan riwayat pengajuan beserta status terkininya, mulai dari menunggu verifikasi hingga keputusan akhir.'],
            ] as $i => $item)
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
                <button type="button" @click="openIndex = (openIndex === {{ $i }} ? -1 : {{ $i }})"
                        class="w-full flex items-center justify-between gap-4 px-6 py-5 text-left">
                    <span class="font-semibold text-slate-800">{{ $item['q'] }}</span>
                    <svg class="w-5 h-5 text-slate-400 shrink-0 transition-transform" :class="openIndex === {{ $i }} ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openIndex === {{ $i }}" x-transition.duration.200ms>
                    <p class="px-6 pb-5 text-sm text-slate-500 leading-relaxed">{{ $item['a'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </section>

    {{-- ── CTA ─────────────────────────────────────────────────────────── --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-24">
        <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 to-indigo-700 rounded-3xl px-8 py-16 sm:px-16 text-center shadow-xl shadow-blue-600/20">
            <div class="absolute -top-16 -right-16 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-16 -left-16 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
            <div class="relative">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">Siap mempercepat keputusan kredit Anda?</h2>
                <p class="mt-4 text-blue-100 max-w-xl mx-auto">Buat akun dan rasakan bagaimana analisis berbasis AI membantu Anda menilai risiko lebih cepat dan lebih percaya diri.</p>
                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="inline-flex items-center gap-2 bg-white text-blue-700 font-semibold px-7 py-3.5 rounded-xl hover:bg-blue-50 transition shadow-md">
                            Buka Dashboard
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-white text-blue-700 font-semibold px-7 py-3.5 rounded-xl hover:bg-blue-50 transition shadow-md">
                            Daftar Sekarang — Gratis
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-white font-semibold px-7 py-3.5 rounded-xl border border-white/30 hover:bg-white/10 transition">
                            Masuk ke Akun
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </section>

    {{-- ── FOOTER ──────────────────────────────────────────────────────── --}}
    <footer class="border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <p class="text-sm text-slate-500">© {{ date('Y') }} CrediSense AI. Seluruh hak cipta dilindungi.</p>
            </div>
            <p class="text-sm text-slate-400">Dibangun dengan Laravel v{{ app()->version() }}</p>
        </div>
    </footer>

</body>
</html>
