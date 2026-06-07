<x-guest-layout>
    <div class="mb-7">
        <h1 class="text-2xl font-bold text-slate-900">Konfirmasi kata sandi</h1>
        <p class="mt-1.5 text-sm text-slate-400">{{ __('Ini adalah area aman aplikasi. Mohon konfirmasi kata sandi Anda sebelum melanjutkan.') }}</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button class="w-full mt-2">
            {{ __('Confirm') }}
        </x-primary-button>
    </form>
</x-guest-layout>
