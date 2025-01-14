<x-app-layout>

<div class="py-6">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class=" text-xl py-8 px-10 bg-white dark:bg-gray-800 font-medium text-gray-900 dark:text-gray-100 overflow-hidden shadow-sm sm:rounded-lg">
        <h3 class="text-center pb-5">取引種別を新規作成</h3>    
        <form action="/tradeType/create" method="POST" enctype="multipart/form-data" class="">
                @csrf
                <div class="">

                <!-- TradeType Input -->
                <div class="mb-4">
                    <label for="trade_type">Type ID</label>
                    <input type="number" name="trade_type" placeholder="trade type id" class="block w-full text-xl dark:bg-gray-900 mt-1 py-3 shadow-sm border-gray-300 dark:border-gray-600 rounded-md">
                </div>

                <!-- Name Input -->
                <div class="mb-4">
                    <label for="name">Name</label>
                    <input type="text" name="name" placeholder="name" class="block w-full text-xl dark:bg-gray-900 mt-1 py-3 shadow-sm border-gray-300 dark:border-gray-600 rounded-md">
                </div>

                <!-- Caption Input -->
                <div class="mb-4">
                    <label for="caption">Caption</label>
                    <input type="text" name="caption" placeholder="caption" class="block w-full text-xl dark:bg-gray-900 mt-1 py-3 shadow-sm border-gray-300 dark:border-gray-600 rounded-md">
                </div>

                <x-primary-button class="float-right px-16 py-3 mt-8 lg:m-4">
                    保存
                </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>