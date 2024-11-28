<x-app-layout>
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 font-sans text-gray-900 dark:text-gray-100 px-5 shadow-sm bg-clip-border rounded-lg">
        <div class="p-6">
        <div class="mb-4">
            <p class="w-4/5 text-center text-lg font-bold leading-snug tracking-normal antialiased">
                預け状況
            </p>
        </div>
        <div class="grid grid-cols-1 divide-y">
            @foreach($items as $item)
            <div class="py-2 font-sans text-2xl">
                <a href="/depo_detail/{{ $item['member_code'] }}" class="flex justify-between">
                    <div class="w-4/5">
                        {{ $item['name'] }}
                    </div>
                    <div class="w-1/5 text-right">
                        {{ $item['depo_status'] }}
                    </div>
                </a>
            </div>
            @endforeach
        </div>
        </div>
    </div>
    </div>
</div>
</x-app-layout>
