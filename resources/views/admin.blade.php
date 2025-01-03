<x-app-layout>
    <div class="py-5 text-gray-900 dark:text-gray-100">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="text-lg font-medium bg-white dark:bg-gray-800 py-6 px-5 overflow-hidden shadow-sm sm:rounded-lg">

                <div class="m-4">
                    <!-- 営業所ページへのリンク -->
                    <form action="/show_dashboard" method="POST" enctype="multipart/form-data" class="mb-5">
                        @csrf
                        <label for="user_dashboard" class="block pb-1 lg:pb-0">ユーザーのダッシュボードを表示</label>
                        <select name="user_dashboard" id="user_dashboard" class="w-full lg:w-1/2 text-lg dark:bg-gray-900 py-2 px-8 shadow-sm border-gray-300 dark:border-gray-600 rounded-md">
                            @foreach($users as $user)
                                <option value="{{ $user['member_code'] }}" class="">
                                    {{ $user->name . "（" . $user->sub_now . "）" }}
                                </option>
                            @endforeach
                        </select>
                        <x-primary-button class="px-5 my-2 lg:ml-5">
                            show Dashboard
                        </x-primary-button>
                    </form>

                    <!-- 個人を更新 -->
                    <form action="/refresh_member" method="POST" enctype="multipart/form-data" class="mb-5">
                        @csrf
                        <label for="member_code" class="block pb-1 lg:pb-0">更新するユーザーを選択</label>
                        <select name="member_code" id="member_code" class="w-full lg:w-1/2 text-lg dark:bg-gray-900 py-2 px-8 shadow-sm border-gray-300 dark:border-gray-600 rounded-md">
                            @foreach($users as $user)
                                <option value="{{ $user['member_code'] }}" class="">
                                    {{ $user->name . "（" . $user->sub_now . "）" }}
                                </option>
                            @endforeach
                        </select>
                        <x-primary-button class="px-5 my-2 lg:ml-5">
                            refresh
                        </x-primary-button>
                    </form>

                    <!-- 取引を編集 -->
                    <form action="/trade/edit" method="GET" enctype="multipart/form-data" class="mb-5">
                        @csrf
                        <label for="edit_id" class="block pb-1 lg:pb-0">編集する取引を選択</label>
                        <select name="edit_id" id="edit_id" class="w-full lg:w-1/2 text-lg dark:bg-gray-900 py-2 px-8 shadow-sm border-gray-300 dark:border-gray-600 rounded-md">
                            @foreach($trades as $trade)
                                <option value="{{ $trade['id'] }}" class="">
                                    {{ $trade['id'] . ' （' . $trade['date'].  '） ' . $trade['trade_type'] . ' : ' . $trade->user->name . ' : ' . $trade['amount'] . 'セット' }}
                                </option>
                            @endforeach
                        </select>
                        <x-primary-button class="px-11 my-2 lg:ml-5">
                            edit
                        </x-primary-button>
                    </form>

                    <!-- 削除 -->
                    <form action="/delete" method="POST" enctype="multipart/form-data" onsubmit="return confirmDelete()">
                        @csrf
                        <label for="trade_id" class="block pb-1 lg:pb-0">削除する取引を選択</label>
                        <select name="trade_id" id="trade_id" class="w-full lg:w-1/2 text-lg dark:bg-gray-900 py-2 px-8 shadow-sm border-gray-300 dark:border-gray-600 rounded-md">
                            @foreach($trades as $trade)
                                <option value="{{ $trade['id'] }}" class="">
                                {{ $trade['id'] . ' （' . $trade['date'].  '） ' . $trade['trade_type'] . ' : ' . $trade->user->name . ' : ' . $trade['amount'] . 'セット' }}
                                </option>
                            @endforeach
                        </select>
                        <x-primary-button class="px-7 my-2 lg:ml-5">
                            Delete
                        </x-primary-button>
                    </form>
                </div>
                
                <div class="p-4 flex">
                    <!-- 更新 -->
                    <a href="/refresh_all">
                        <x-primary-button class="px-10 mr-5 lg:mr-10">
                            Refresh All
                        </x-primary-button>
                    </a>
                    <!-- 実績＆最新の取引＆資格手当をリセット -->
                    <a href="/reset_all">
                        <x-danger-button class="px-8 py-3 my-4 lg:my-0">
                            Reset
                        </x-danger-button>
                    </a>
                </div>
                
                <div class="py-10 font-normal">

                    @foreach($refreshLogs as $refreshLog)
                        @if (is_string($refreshLog))
                            <div class="p-3 mb-2 bg-red-200 dark:bg-red-800">{{ $refreshLog }}</div>
                        @else
                            <div class="{{ $refreshLog->status == 'success' ? 'bg-green-200 dark:bg-green-800' : 'bg-red-200 dark:bg-red-800' }} p-3 mb-2">
                                {{ $refreshLog->caption . "：" . $refreshLog->created_at }}
                            </div>
                        @endif
                    @endforeach

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
