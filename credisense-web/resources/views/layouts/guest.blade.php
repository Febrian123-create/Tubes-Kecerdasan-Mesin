<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'CrediSense AI') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-800">
        <div class="min-h-screen flex">

            {{-- ── Brand panel (hidden on small screens) ──────────────────── --}}
            <div class="hidden lg:flex lg:w-[44%] relative overflow-hidden bg-gradient-to-br from-blue-600 to-indigo-800 text-white flex-col justify-between p-12">
                <div class="absolute -top-24 -right-24 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-16 w-72 h-72 bg-indigo-400/20 rounded-full blur-3xl"></div>

                <a href="/" class="relative flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/15 backdrop-blur rounded-xl flex items-center justify-center ring-1 ring-white/20">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div class="leading-tight">
                        <p class="font-bold">CrediSense AI</p>
                        <p class="text-blue-200 text-xs">Credit Risk Platform</p>
                    </div>
                </a>

                <div class="relative">
                    <h2 class="text-3xl font-extrabold leading-tight tracking-tight">
                        Penilaian risiko kredit yang lebih cepat &amp; transparan
                    </h2>
                    <p class="mt-4 text-blue-100 leading-relaxed max-w-md">
                        Masuk untuk mengelola pengajuan, memantau skor risiko berbasis AI, dan menindaklanjuti
                        verifikasi data SLIK OJK — semua dalam satu dashboard.
                    </p>

                    <ul class="mt-8 space-y-3">
                        @foreach(['Skor risiko instan dari model AI','Verifikasi data SLIK terintegrasi','Pantau status pengajuan secara real-time'] as $point)
                        <li class="flex items-center gap-3 text-sm text-blue-50">
                            <span class="w-6 h-6 rounded-full bg-white/15 flex items-center justify-center shrink-0 ring-1 ring-white/20">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            {{ $point }}
                        </li>
                        @endforeach
                    </ul>
                </div>

                <p class="relative text-blue-200 text-xs">© {{ date('Y') }} CrediSense AI. Seluruh hak cipta dilindungi.</p>
            </div>

            {{-- ── Form panel ──────────────────────────────────────────────── --}}
            <div class="flex-1 flex flex-col items-center justify-center px-6 py-12 bg-slate-50">
                <div class="w-full max-w-md">
                    {{-- Mobile brand header --}}
                    <a href="/" class="lg:hidden flex items-center justify-center gap-3 mb-8">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-600/20">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <div class="leading-tight text-left">
                            <p class="font-bold text-slate-900 text-sm">CrediSense AI</p>
                            <p class="text-slate-400 text-[11px]">Credit Risk Platform</p>
                        </div>
                    </a>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-6 py-8 sm:px-10">
                        {{ $slot }}
                    </div>

                    <p class="mt-6 text-center text-sm text-slate-400">
                        <a href="/" class="hover:text-slate-600 transition">← Kembali ke beranda</a>
                    </p>
                </div>
            </div>
        </div>
    </body>
</html>
