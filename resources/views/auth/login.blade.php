<x-guest-layout>
    <div class="p-6">
    <!-- Session Status -->
    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <!-- <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div> -->
        
        <!-- Member Code -->
        <div>
            <x-input-label for="member_code" value="営業所コード（例：3851-00011の場合→11）" />
            <x-text-input id="member_code" class="block mt-1 w-full" type="text" name="member_code" :value="old('member_code')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('member_code')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-10">
            <x-input-label for="password" :value="__('パスワード（ご登録の電話番号の下4桁）')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
 
        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="remember" checked>
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('記憶する') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="hidden underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="px-10 text-lg">
                {{ __('ログイン') }}
            </x-primary-button>
        </div>
    </form>
    </div>
</x-guest-layout>
