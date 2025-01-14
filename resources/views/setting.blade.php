<x-app-layout>
    <div class="py-5 text-gray-900 dark:text-gray-100">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="text-lg font-medium bg-white dark:bg-gray-800 py-6 px-10 overflow-hidden shadow-sm sm:rounded-lg">

                <!-- 取引種別の編集 -->
                <div class="text-center pb-5 lg:w-1/3">
                    <p>取引種別</p>
                    <x-link-button :href="route('tradeType.create')" class="my-3 py-2 w-full">
                        新規作成
                    </x-link-button>
                    <form action="/tradeType/edit" method="GET" enctype="multipart/form-data" class="mb-5">
                        @csrf
                        <select name="id" class="w-full text-lg dark:bg-gray-900 py-2 px-4 shadow-sm border-gray-300 dark:border-gray-600 rounded-md">
                            @foreach($tradeTypes as $type)
                                <option value="{{ $type->id }}" class="">
                                    {{ $type->trade_type . " : " . $type->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-primary-button class="mt-2 w-full">
                            編集
                        </x-primary-button>
                    </form>
                </div>

                <!-- 商品名の編集 -->
                <div class="text-center pb-5 lg:w-1/3">
                    <p>商品</p>
                    <x-link-button :href="route('product.create')" class="my-3 py-2 w-full">
                        新規作成
                    </x-link-button>
                    <form action="/product/edit" method="GET" enctype="multipart/form-data" class="mb-5">
                        @csrf
                        <select name="id" class="w-full text-lg dark:bg-gray-900 py-2 px-4 shadow-sm border-gray-300 dark:border-gray-600 rounded-md">
                            @foreach($products as $product)
                                <option value="{{ $type->id }}" class="">
                                    {{ $product->product_type . " : " . $product->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-primary-button class="mt-2 w-full">
                            編集
                        </x-primary-button>
                    </form>
                </div>

            </div>
        </div>
    </div>

</x-app-layout>
