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
    <div class="py-12 text-gray-900 dark:text-gray-100">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 pb-8 px-5 overflow-hidden shadow-sm sm:rounded-lg">

                <!-- 更新 -->
                <div class="p-10">
                    <a href="/refresh_all">
                        <x-primary-button class="px-6 items-center justify-center">
                            Refresh
                        </x-primary-button>
                    </a>
                </div>

                
                <div class="m-4">
                    <!-- 営業所ページへのリンク -->
                    <form action="/show_dashboard" method="POST" enctype="multipart/form-data" class="mb-5">
                        @csrf
                        <label for="user_dashboard" class="text-sm font-medium">ユーザーを選択（ダッシュボードを表示）</label>
                        <div class="">
                        <select name="user_dashboard" id="user_dashboard" class="w-2/6 min-w-56 dark:bg-gray-900 mt-1 mr-5 py-2 px-8 shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md">
                            @foreach($users as $user)
                                <option value="{{ $user['member_code'] }}" class="">
                                    {{ $user->name . "（" . $user->sub_now . "）" }}
                                </option>
                            @endforeach
                        </select>
                        <x-primary-button class="px-5 my-2 items-center justify-center">
                            show Dashboard
                        </x-primary-button>
                        </div>
                    </form>

                    <form action="/admin" method="POST" enctype="multipart/form-data" class="mb-5">
                        @csrf
                        <label for="trading" class="text-sm font-medium">詳細を表示する取引を選択</label>
                        <div class="">
                        <select name="trading" id="trading" class="w-2/6 min-w-56 dark:bg-gray-900 mt-1 mr-5 py-2 px-8 shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md">
                            @foreach($trades as $trade)
                                <option value="{{ $trade['id'] }}" class="">
                                    {{ $trade['id'] . ' （' . $trade['date'].  '） ' . $trade->user->name . ' : ' . $trade['amount'] . 'セット' }}
                                </option>
                            @endforeach
                        </select>
                        <x-primary-button class="px-5 my-2 items-center justify-center">
                            Display
                        </x-primary-button>
                        </div>
                    </form>

                    <!-- 個人を更新 -->
                    <form action="/refresh_member" method="POST" enctype="multipart/form-data" class="mb-5">
                        @csrf
                        <label for="member_code" class="text-sm font-medium">更新するユーザーを選択</label>
                        <div class="">
                        <select name="member_code" id="member_code" class="w-2/6 min-w-56 dark:bg-gray-900 mt-1 mr-5 py-2 px-8 shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md">
                            @foreach($users as $user)
                                <option value="{{ $user['member_code'] }}" class="">
                                    {{ $user->name . "（" . $user->sub_now . "）" }}
                                </option>
                            @endforeach
                        </select>
                        <x-primary-button class="text-red-600 px-5 my-2 items-center justify-center">
                            refresh
                        </x-primary-button>
                        </div>
                    </form>

                    <!-- 削除 -->
                    <form action="/delete" method="POST" enctype="multipart/form-data">
                        @csrf
                        <label for="trade_id" class="text-sm font-medium">削除する取引を選択</label>
                        <div class="">
                        <select name="trade_id" id="trade_id" class="w-2/6 min-w-56 dark:bg-gray-900 mt-1 mr-5 py-2 px-8 shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md">
                            @foreach($trades as $trade)
                                <option value="{{ $trade['id'] }}" class="">
                                    {{ $trade['id'] . ' （' . $trade['date'].  '） ' . $trade->user->name . ' : ' . $trade['amount'] . 'セット' }}
                                </option>
                            @endforeach
                        </select>
                        <x-primary-button class="text-red-600 px-5 my-2 items-center justify-center">
                            Delete
                        </x-primary-button>
                        </div>
                    </form>

                    <!-- 実績＆最新の取引＆資格手当をリセット -->
                    <div class="p-10">
                        <a href="/reset_all">
                            <x-danger-button class="px-6 items-center justify-center">
                                Reset
                            </x-danger-button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @if (isset($display))
        <div class="p-8">
        <div class="mb-4">
            <p class="text-2xl font-bold leading-snug tracking-normal antialiased">
                {{ $display->user->name . " : " . $display->amount }}
            </p>
        </div>
        <div class="max-w-xl grid grid-cols-1 divide-y">
            @foreach($details as $detail)
            <div class="flex py-2 font-sans text-xl">
                <div class="w-11/12 mt-0.5">
                    {{ str_replace('　', ' ', $detail->product->name) }}
                </div>
                <div class="w-1/12 t text-2xl text-right">
                    {{ $detail['amount'] }}
                </div>
            </div>
            @endforeach
        </div>
        </div>
    @endif
    </div>
</x-app-layout>
