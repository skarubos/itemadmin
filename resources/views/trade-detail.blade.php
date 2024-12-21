<x-app-layout>
<div class="pt-3 pb-8">
    <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 font-sans text-gray-900 dark:text-gray-100 p-6 shadow-sm bg-clip-border rounded-lg">
        <div class="mb-4">
            <p class="text-center text-2xl font-bold mb-2">お取引詳細</p>
            <div class="bg-gray-100 dark:bg-gray-900  text-xl p-6 shadow-sm bg-clip-border rounded-lg">
                <p class="">
                    {{ $trade->user->name }}
                </p>
                <p class="">
                    {{ \Carbon\Carbon::parse($trade->date)->format('Y年n月j日') }}
                </p>
                <p class="">
                    取引種別：{{ $trade->tradeType->name }}
                </p>
                <p class="">
                    合計：{{ $trade->amount }}セット
                </p>
            </div>
        </div>
        <div class="grid grid-cols-1 divide-y">
            @foreach($details as $detail)
            <div class="flex py-2 font-sans text-xl">
                <div class="w-5/6 mt-0.5">
                    {{ str_replace('　', ' ', $detail->product->name) }}
                </div>
                <div class="w-2/12 text-2xl text-right">
                    {{ $detail['amount'] }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
    </div>
</div>
</x-app-layout>
