<x-app-layout>

<div class="py-6">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class=" text-xl py-8 px-10 bg-white dark:bg-gray-800 font-medium text-gray-900 dark:text-gray-100 overflow-hidden shadow-sm sm:rounded-lg">
        <h3 class="text-center pb-5">取引種別を編集</h3>    
        <form id="edit-delete-form" method="POST" enctype="multipart/form-data" class="">
                @csrf
                <div class="">
                <!-- ID（非表示） -->
                <input type="hidden" name="id" value="{{ $tradeType->id }}">

                <!-- TradeType Input -->
                <div class="mb-4">
                    <label for="trade_type">ID</label>
                    <input type="number" name="trade_type" value="{{ $tradeType->trade_type }}" placeholder="{{ $tradeType->trade_type }}" class="block w-full text-xl dark:bg-gray-900 mt-1 py-3 shadow-sm border-gray-300 dark:border-gray-600 rounded-md">
                    <input type="hidden" name="trade_type_old" value="{{ $tradeType->trade_type }}">
                </div>

                <!-- Name Input -->
                <div class="mb-4">
                    <label for="name">Name</label>
                    <input type="text" name="name" value="{{ $tradeType->name }}" placeholder="{{ $tradeType->name }}" class="block w-full text-xl dark:bg-gray-900 mt-1 py-3 shadow-sm border-gray-300 dark:border-gray-600 rounded-md">
                </div>

                <!-- Caption Input -->
                <div class="mb-4">
                    <label for="caption">Caption</label>
                    <input type="text" name="caption" value="{{ $tradeType->caption }}" placeholder="{{ $tradeType->caption }}" class="block w-full text-xl dark:bg-gray-900 mt-1 py-3 shadow-sm border-gray-300 dark:border-gray-600 rounded-md">
                </div>
                <x-danger-button onclick="setAction('/tradeType/delete'); return confirmDelete();" class="w-1/3 py-3 mt-10">
                    削除
                </x-danger-button>
                <x-primary-button onclick="setAction('/tradeType/edit')" class="float-right w-7/12 py-3 mt-8">
                    保存
                </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    function setAction(action) {
        var form = document.getElementById('edit-delete-form');
        form.action = action;
    }
    function confirmDelete() {
        return confirm('取引種別（{{ $tradeType->trade_type.":".$tradeType->name }}）を本当に削除しますか？');
    }
</script>
</x-app-layout>