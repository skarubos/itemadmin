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
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 overflow-hidden p-6 shadow-sm sm:rounded-lg">
            <form action="/trade/save" method="POST" enctype="multipart/form-data" class="flex">
                @csrf
                <div class="w-1/2 px-8">
                <!-- No. Input -->
                <div class="mb-4">
                    <label for="no" class="block text-sm font-medium text-gray-900 dark:text-gray-100">No.</label>
                    <input type="number" name="no" id="no" value="{{ $summarys['no'] }}" class="dark:bg-gray-900 mt-1 p-2 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md">
                </div>

                <!-- Name Dropdown -->
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-900 dark:text-gray-100">Name</label>
                    <select name="name" id="name" class="dark:bg-gray-900 mt-1 p-2 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md">
                        <option value="">Select Name</option>
                        @foreach($users as $user)
                            <option value="{{ $user->member_code }}" {{ $summarys['member_code'] == $user->member_code ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Picker -->
                @php
                $date = $summarys['date'] ?? date('Y-m-d');
                @endphp
                <div class="mb-4">
                    <label for="date" class="block text-sm font-medium text-gray-900 dark:text-gray-100">Date</label>
                    <input type="date" name="date" id="date" value="{{ $date }}" class="dark:bg-gray-900 mt-1 p-2 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md">
                </div>

                <!-- Type Dropdown -->
                <div class="mb-4">
                    <label for="type" class="block text-sm font-medium text-gray-900 dark:text-gray-100">Type</label>
                    <select name="type" id="type" class="dark:bg-gray-900 mt-1 p-2 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md">
                        @foreach($trade_types as $trade_type)
                            <option value="{{ $trade_type['trade_type'] }}" {{ $trade_type['trade_type'] == $type ? 'selected' : '' }}>
                                {{ $trade_type['trade_type'] . ' : ' . $trade_type['caption'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Amount Input -->
                <div class="mb-4">
                    <label for="amount" class="block text-sm font-medium text-gray-900 dark:text-gray-100">Amount</label>
                    <input type="number" name="amount" id="amount" value="{{ $summarys['amount'] }}" class="dark:bg-gray-900 mt-1 p-2 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md">
                </div>
                </div>

                <div class="w-1/2 px-8">
                <!-- Details Textarea -->
                <div class="mb-4">
                    <label for="details" class="text-sm font-medium text-gray-900 dark:text-gray-100">Details</label>
                    @foreach($details as $index => $detail)
                    <div class="flex px-4">
                        <input type="hidden" name="details[{{ $index }}][product_id]" value="{{ $detail['product_id'] }}">
                        <input type="text" name="details[{{ $index }}][name]" value="{{ $detail['name'] }}" class="w-5/6 dark:bg-gray-900 mt-1 p-2 block shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md">
                        <input type="number" name="details[{{ $index }}][amount]" value="{{ $detail['amount'] }}"  class="w-1/6 dark:bg-gray-900 mt-1 p-2 block shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md">
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