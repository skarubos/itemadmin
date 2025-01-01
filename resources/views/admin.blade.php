<x-app-layout>
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
                    </form>

                    <!-- 個人を更新 -->
                    <form action="/refresh_member" method="POST" enctype="multipart/form-data" class="mb-5">
                        @csrf
                        <label for="member_code" class="text-sm font-medium">更新するユーザーを選択</label>
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
                    </form>

                    <!-- 取引を編集 -->
                    <form action="/trade/edit" method="GET" enctype="multipart/form-data" class="mb-5">
                        @csrf
                        <label for="edit_id" class="text-sm font-medium">編集する取引を選択</label>
                        <select name="edit_id" id="edit_id" class="w-2/6 min-w-56 dark:bg-gray-900 mt-1 mr-5 py-2 px-6 shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md">
                            @foreach($trades as $trade)
                                <option value="{{ $trade['id'] }}" class="">
                                    {{ $trade['id'] . ' （' . $trade['date'].  '） ' . $trade['trade_type'] . ' : ' . $trade->user->name . ' : ' . $trade['amount'] . 'セット' }}
                                </option>
                            @endforeach
                        </select>
                        <x-primary-button class="px-5 my-2 items-center justify-center">
                            edit
                        </x-primary-button>
                    </form>

                    <!-- 削除 -->
                    <form action="/delete" method="POST" enctype="multipart/form-data" onsubmit="return confirmDelete()">
                        @csrf
                        <label for="trade_id" class="text-sm font-medium">削除する取引を選択</label>
                        <select name="trade_id" id="trade_id" class="w-2/6 min-w-56 dark:bg-gray-900 mt-1 mr-5 py-2 px-6 shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md">
                            @foreach($trades as $trade)
                                <option value="{{ $trade['id'] }}" class="">
                                {{ $trade['id'] . ' （' . $trade['date'].  '） ' . $trade['trade_type'] . ' : ' . $trade->user->name . ' : ' . $trade['amount'] . 'セット' }}
                                </option>
                            @endforeach
                        </select>
                        <x-primary-button class="text-red-600 px-5 my-2 items-center justify-center">
                            Delete
                        </x-primary-button>
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
    </div>
    <script>
        function confirmDelete() {
            return confirm('この取引を本当に削除しますか？');
        }
    </script>
</x-app-layout>
