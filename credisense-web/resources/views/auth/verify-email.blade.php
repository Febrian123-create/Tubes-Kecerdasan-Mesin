<x-guest-layout>
    <div class="mb-7">
        <h1 class="text-2xl font-bold text-slate-900">Verifikasi alamat email</h1>
        <p class="mt-1.5 text-sm text-slate-400 leading-relaxed">
            {{ __('Terima kasih sudah mendaftar! Sebelum memulai, mohon verifikasi alamat email Anda dengan mengklik tautan yang baru saja kami kirimkan. Jika belum menerima email, kami akan dengan senang hati mengirimkannya kembali.') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-5 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">
            {{ __('Tautan verifikasi baru telah dikirim ke alamat email yang Anda gunakan saat mendaftar.') }}
        </div>
    @endif

    <div class="flex items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>
                {{ __('Resend Verification Email') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-slate-400 hover:text-slate-600 font-medium transition">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
