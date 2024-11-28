<x-app-layout>
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 font-sans text-gray-900 dark:text-gray-100 px-5 shadow-sm bg-clip-border rounded-lg">
        <div class="p-6">
        <div class="mb-4">
            <p class="w-4/5 text-center text-lg font-bold leading-snug tracking-normal antialiased">
                {{ $user['name'] }} （合計： {{ $user['depo_status'] }} セット）
            </p>
        </div>
        <div class="grid grid-cols-1 divide-y">
            @foreach($details as $detail)
            <div class="flex justify-between py-2 font-sans text-2xl">
                <div class="w-4/5">
                    {{ $detail->product->name }}
                </div>
                <div class="w-1/5 text-right">
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
