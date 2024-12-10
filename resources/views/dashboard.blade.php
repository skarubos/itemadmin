<x-app-layout>
@if ($errors->any())
    <div class="block bg-red-500 text-white p-10 py-2">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- 成功メッセージ -->
@if (session('success'))
    <div class="block bg-green-500 text-white p-10 py-2">
        {{ session('success') }}
    </div>
@endif
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 pb-8 px-5 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center text-gray-900 dark:text-gray-100">
                    {{ __("You're logged in!") }}
                </div>
                <!-- 更新 -->
                <a href="/refresh_sales">
                    <x-primary-button class="px-10">
                        Refresh
                    </x-primary-button>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
