<x-app-layout>
<div class="pt-3 pb-20">
    <div class="max-w-xl mx-auto lg:px-8">
    <div class="text-xl bg-white dark:bg-gray-800 font-sans text-gray-900 dark:text-gray-100 px-6 py-6 shadow-sm bg-clip-border rounded-lg">
        <div class="my-4">
            <p class="text-center text-xl font-bold leading-snug tracking-normal antialiased">
                新規商品の種類を登録
            </p>
        </div>
        @foreach($newProducts as $product)
        <div class="mb-5 py-5 font-sans bg-gray-100 dark:bg-gray-900 rounded-2xl">
        <form action="/product/edit" method="POST" enctype="multipart/form-data" class="">
            @csrf
            <!-- 商品ID（非表示） -->
            <input type="hidden" name="id" value="{{ $product->id }}">
            <!-- 商品名（非表示） -->
            <input type="hidden" name="name" value="{{ $product->name }}">
            <!-- 残り件数（非表示） -->
            <input type="hidden" name="remain" value="{{ count($newProducts) }}">
            <div class="text-center py-3">
                {{ $product->name }}
            </div>
            <div class="mb-4">
                <select name="product_type" class="block text-xl dark:bg-gray-900 mx-auto mt-1 py-3 shadow-sm border-gray-300 dark:border-gray-600 rounded-md">
                    <option value="">商品の種類を選択</option>
                    @foreach($types as $index => $type)
                        <option value="{{ $index + 1 }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <x-primary-button class="block w-2/3 py-3 mt-8 mx-auto">
                保存
            </x-primary-button>
        </form>
        </div>
        @endforeach
    </div>
    </div>
</div>
</x-app-layout>
