<x-app-layout>

<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="text-xl bg-white dark:bg-gray-800 font-medium text-gray-900 dark:text-gray-100 overflow-hidden px-4 lg:px-8 py-8 shadow-sm sm:rounded-lg">
            <form action="/trade/edit" method="POST" enctype="multipart/form-data">
            <div class="sm:flex">
                @csrf
                <!-- 取引ID（非表示） -->
                <input type="hidden" name="trade_id" value="{{ $trade->id ?? '' }}">
                <!-- Status（非表示） -->
                <input type="hidden" name="status" value="1">
                <!-- 残り取引件数（自動登録取引チェック時）（非表示） -->
                <input type="hidden" name="remain" value="{{ isset($remain) ? $remain : 0 }}">

                <div class="lg:w-1/2 px-8">

                <!-- Date Picker -->
                @php
                $date = $trade->date ?? date('Y-m-d');
                @endphp
                <div class="mb-4">
                    <label for="date" class="">注文日</label>
                    <input type="date" name="date" id="date" value="{{ $date }}" {{ isset($remain) ? 'readonly' : '' }} class="block w-full text-xl dark:bg-gray-900 mt-1 py-3 shadow-sm border-gray-300 dark:border-gray-600 rounded-md">
                </div>

                <!-- Amount Input -->
                <div class="mb-4">
                    <label for="amount" class="">セット数</label>
                    <input type="number" name="amount" id="amount" value="{{ $trade->amount }}" {{ isset($remain) ? 'readonly' : '' }} class="block w-full text-xl dark:bg-gray-900 mt-1 py-3 shadow-sm border-gray-300 dark:border-gray-600 rounded-md">
                </div>

                <!-- member_code Dropdown -->
                <div class="mb-4">
                    <label for="member_code" class="">注文者</label>
                    <select name="member_code" id="member_code" class="block w-full text-xl dark:bg-gray-900 mt-1 py-3 shadow-sm border-gray-300 dark:border-gray-600 rounded-md">
                        <option value="" class="text-gray-500">名前を選択</option>
                        @foreach($users as $user)
                            <option value="{{ $user->member_code }}" {{ $user->member_code == $trade->member_code ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- tradeType Dropdown -->
                <div class="mb-4">
                    <label for="trade_type" class="">取引種別</label>
                    <select name="trade_type" id="trade_type" class="block w-full text-xl dark:bg-gray-900 mt-1 py-3 shadow-sm border-gray-300 dark:border-gray-600 rounded-md">
                    @if($trade_types instanceof \App\Models\TradeType)
                        <option value="{{ $trade_types->trade_type }}" selected>
                            {{ $trade_types->trade_type . ' : ' . $trade_types->caption }}
                        </option>
                    @else
                        <option value="" class="text-gray-500">取引種別を選択</option>
                        @foreach($trade_types as $trade_type)
                        <option value="{{ $trade_type->trade_type }}" {{ $trade_type->trade_type == $trade->trade_type ? 'selected' : '' }}>
                            {{ $trade_type->trade_type . ' : ' . $trade_type->caption }}
                        </option>
                        @endforeach
                    @endif
                    </select>
                </div>
                </div>

                <!-- $detailsが渡されていない場合は非表示 -->
                <div class="{{ isset($details) ? '' : 'hidden' }} w-1/2">
                    <div class="px-8 pt-3 lg:w-full">
                    <!-- Details Textarea -->
                    <div class="mb-4">
                    @isset($details)
                        <label for="details" class="mr-10">取引詳細</label>
                        <input type="{{ is_null($trade->id) ? 'hidden' : 'checkbox' }}" name="change_detail" value="1">
                        @if(!is_null($trade->id))
                            <label for="change_detail">変更あり</label>
                        @endif
                        @foreach($details as $index => $detail)
                        <div class="flex lg:px-4">
                            <input type="hidden" name="details[{{ $index }}][product_id]" value="{{ $detail->product_id }}">
                            <input type="text" name="details[{{ $index }}][name]" value="{{ $detail->name }}" class="w-5/6 dark:bg-gray-900 mt-1 p-2 block shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md">
                            <input type="number" name="details[{{ $index }}][amount]" value="{{ $detail->amount }}"  class="w-1/6 dark:bg-gray-900 mt-1 p-2 block shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md">
                        </div>
                        @endforeach
                    @endisset
                    </div>
                    </div>
                </div>

            </div>
            <div class="w-3/4 pt-6 mx-auto sm:w-1/2">
                <x-primary-button class="w-full py-3">
                    保存
                </x-primary-button>
            </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>