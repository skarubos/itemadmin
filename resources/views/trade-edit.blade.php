<x-app-layout>

<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 font-medium text-gray-900 dark:text-gray-100 overflow-hidden p-6 shadow-sm sm:rounded-lg">
            <form action="/trade/save" method="POST" enctype="multipart/form-data" class="flex">
                @csrf
                <!-- 取引ID（非表示） -->
                <input type="hidden" name="trade_id" value="{{ $trade->id ?? '' }}">

                <div class="w-1/2 px-8">

                <!-- member_code Dropdown -->
                <div class="mb-4">
                    <label for="member_code" class="">Name</label>
                    <select name="member_code" id="member_code" class="dark:bg-gray-900 mt-1 p-2 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md">
                        <option value="">Select Name</option>
                        @foreach($users as $user)
                            <option value="{{ $user->member_code }}" {{ $user->member_code == $trade->member_code ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Picker -->
                @php
                $date = $trade->date ?? date('Y-m-d');
                @endphp
                <div class="mb-4">
                    <label for="date" class="">Date</label>
                    <input type="date" name="date" id="date" value="{{ $date }}" class="dark:bg-gray-900 mt-1 p-2 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md">
                </div>

                <!-- tradeType Dropdown -->
                <div class="mb-4">
                    <label for="trade_type" class="">Type</label>
                    <select name="trade_type" id="trade_type" class="dark:bg-gray-900 mt-1 p-2 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md">
                        @foreach($trade_types as $trade_type)
                            <option value="{{ $trade_type['trade_type'] }}" {{ $trade_type['trade_type'] == $trade->trade_type ? 'selected' : '' }}>
                                {{ $trade_type['trade_type'] . ' : ' . $trade_type['caption'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Amount Input -->
                <div class="mb-4">
                    <label for="amount" class="">Amount</label>
                    <input type="number" name="amount" id="amount" value="{{ $trade->amount }}" class="dark:bg-gray-900 mt-1 p-2 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md">
                </div>
                </div>

                <div class="w-1/2 px-8">
                <!-- Details Textarea -->
                <div class="mb-4">
                    <label for="details" class="mr-10">Details</label>
                    <input type={{ is_null($trade->id) ? "hidden" : "checkbox" }} name="change_detail" value="1">
                    @if(!is_null($trade->id))
                        <label for="change_detail">変更あり</label>
                    @endif
                    @foreach($details as $index => $detail)
                    <div class="flex px-4">
                        <input type="hidden" name="details[{{ $index }}][product_id]" value="{{ $detail->product_id }}">
                        <input type="text" name="details[{{ $index }}][name]" value="{{ $detail->name }}" class="w-5/6 dark:bg-gray-900 mt-1 p-2 block shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md">
                        <input type="number" name="details[{{ $index }}][amount]" value="{{ $detail->amount }}"  class="w-1/6 dark:bg-gray-900 mt-1 p-2 block shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md">
                    </div>
                    @endforeach
                </div>

                <x-primary-button class="float-right m-4 px-10">
                    Save
                </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>