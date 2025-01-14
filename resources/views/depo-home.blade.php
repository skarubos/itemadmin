<x-app-layout>
<div class="pt-3 pb-8">
    <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 font-sans text-gray-900 dark:text-gray-100 px-5 shadow-sm bg-clip-border rounded-lg">
        <div class="p-6">
        <div class="mb-4 text-xl font-bold text-center">
            <p>預け</p>
            <p class="text-lg pt-2">
                合計 <span class="text-2xl">{{ $sumDepoStatus }}</span> セット
            </p>
        </div>
        <div class="grid grid-cols-1 divide-y">
            @foreach($items as $item)
            <div class="py-2 font-sans text-2xl">
                <a href="/depo/member/{{ $item['member_code'] }}" class="flex justify-between">
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
