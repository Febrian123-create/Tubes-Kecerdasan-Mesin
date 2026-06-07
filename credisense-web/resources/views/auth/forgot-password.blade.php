<x-guest-layout>
    <div class="mb-7">
        <h1 class="text-2xl font-bold text-slate-900">Lupa kata sandi?</h1>
        <p class="mt-1.5 text-sm text-slate-400">{{ __('Masukkan alamat email Anda dan kami akan mengirimkan tautan untuk membuat kata sandi baru.') }}</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="w-full mt-2">
            {{ __('Email Password Reset Link') }}
        </x-primary-button>
    </form>
</x-guest-layout>
