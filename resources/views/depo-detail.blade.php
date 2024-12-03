<x-app-layout>
<div class="pt-3 pb-8">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 font-sans text-gray-900 dark:text-gray-100 px-5 shadow-sm bg-clip-border rounded-lg">
        <div class="p-6">
        <div class="mb-4">
            <p class="text-center text-2xl font-bold leading-snug tracking-normal antialiased">
                {{ str_replace('　', ' ', $user['name']) }} （{{ $user['depo_status'] }} セット）
            </p>
        </div>
        <div class="grid grid-cols-1 divide-y">
            @foreach($details as $detail)
            <div class="flex py-2 font-sans text-xl">
                <div class="w-11/12 mt-0.5">
                    {{ str_replace('　', ' ', $detail->product->name) }}
                </div>
                <div class="w-1/12 t text-2xl text-right">
                    {{ $detail['amount'] }}
                </div>
            </div>
            @endforeach
        </div>
        </div>
    </div>
    </div>
</div>
</x-app-layout>
