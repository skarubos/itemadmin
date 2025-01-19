<x-app-layout>

<div class="py-6">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class=" text-xl py-8 px-10 bg-white dark:bg-gray-800 font-medium text-gray-900 dark:text-gray-100 overflow-hidden shadow-sm sm:rounded-lg">
        <h3 class="text-center pb-5">商品を編集</h3>    
        <form id="edit-delete-form" method="POST" enctype="multipart/form-data" class="">
                @csrf
                <div class="">

                <!-- ID Input -->
                <div class="mb-4">
                    <label for="id">ID（編集不可）</label>
                    <input readonly type="number" name="id" value="{{ $product->id }}" placeholder="{{ $product->id }}" class="block w-full text-xl dark:bg-gray-900 mt-1 py-3 shadow-sm border-gray-300 dark:border-gray-600 rounded-md">
                </div>

                <!-- Name Input -->
                <div class="mb-4">
                    <label for="name">Name（編集不可）</label>
                    <input readonly type="text" name="name" value="{{ $product->name }}" placeholder="{{ $product->name }}" class="block w-full text-xl dark:bg-gray-900 mt-1 py-3 shadow-sm border-gray-300 dark:border-gray-600 rounded-md">
                </div>

                <!-- ProductType Input -->
                <div class="mb-4">
                    <label for="product_type">商品種別</label>
                    <select name="product_type" class="block w-full text-xl dark:bg-gray-900 mx-auto mt-1 py-3 shadow-sm border-gray-300 dark:border-gray-600 rounded-md">
                    <option value="">商品の種類を選択</option>
                    @foreach($types as $index => $type)
                        <option value="{{ $index + 1 }}">{{ $type }}</option>
                    @endforeach
                </select>
                    <input type="hidden" name="product_type_old" value="{{ $product->product_type }}">
                </div>
                <x-danger-button onclick="setAction('/product/delete'); return confirmDelete();" class="w-1/3 py-3 mt-10">
                    削除
                </x-danger-button>
                <x-primary-button onclick="setAction('/product/edit')" class="float-right w-7/12 py-3 mt-8">
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
        return confirm('商品（{{ $product->id.":".$product->name }}）を本当に削除しますか？');
    }
</script>
</x-app-layout>