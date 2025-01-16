<x-app-layout>
    <div class="py-5 text-gray-900 dark:text-gray-100 sm:flex sm:px-3 lg:px-8">
        <!-- テーブルごとのCRUD用UI表示 -->
        @foreach($items as $i => $item)
        <div class="mb-5 sm:w-1/2 sm:px-5 lg:w-1/3 flex-grow-0 flex-shrink-0">
            <div class="text-lg font-medium bg-white dark:bg-gray-800 py-6 px-10 overflow-hidden shadow-sm sm:rounded-lg sm:pb-12">
                <div class="text-center">
                    <p>{{ $item['label'] }}</p>
                    <x-link-button :href="route( $item['route'][0] )" class="my-3 py-2 w-full">
                        新規作成
                    </x-link-button>
                    <form action="{{ route( $item['route'][1] ) }}" method="GET" enctype="multipart/form-data">
                        @csrf
                        <select name="id" class="w-full text-lg dark:bg-gray-900 py-2 px-4 shadow-sm border-gray-300 dark:border-gray-600 rounded-md">
                            @foreach($selects[$i] as $select)
                                <option value="{{ $select->id }}" class="">
                                    {{ $select->{ $item['key'][0] } . " : " . $select->{ $item['key'][1] } }}
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
        @endforeach
    </div>

</x-app-layout>
