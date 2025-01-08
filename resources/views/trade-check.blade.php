<x-app-layout>
<div class="pt-3 pb-20">
    <div class="max-w-xl mx-auto lg:px-8">
    <div class="bg-white dark:bg-gray-800 font-sans text-gray-900 dark:text-gray-100 px-6 py-6 shadow-sm bg-clip-border rounded-lg">
        <div class="my-4">
            <p class="text-center text-xl font-bold leading-snug tracking-normal antialiased">
                新規取引
            </p>
        </div>
        @foreach($newTrade as $trade)
        <div class="mb-5 py-5 font-sans text-2xl bg-gray-100 dark:bg-gray-900 rounded-2xl">
            <div class="text-center py-3">
                <p>{{ \Carbon\Carbon::parse($trade->date)->format('Y年n月j日') }}</p>
                <p>{{ $trade->amount }} セット</p>
            </div>
            <div class="mx-5 mb-5 py-4 px-3 bg-white dark:bg-gray-800 rounded-2xl">
                <p class="text-xl mb-1">氏名 <span class="text-2xl font-bold">{{ $trade->user->name }}</span></p>
                <p class="text-xl">取引種別　<span class="text-2xl font-bold">{{ $trade->tradeType->name }}</span></p>
            </div>
            
            <div class="grid grid-flow-col justify-stretch mb-5">
                <a href="/trade/edit/{{ $trade->id }}/{{ count($newTrade) }}" class="inline-block ml-5 mr-3">
                    <div class="text-center py-5 bg-yellow-200 dark:bg-sky-900 rounded-2xl shadow-lg">
                        変更
                    </div>
                </a>
                <a href="/trade/checked/{{ $trade->id }}/{{ count($newTrade) }}" class="inline-block ml-3 mr-5">
                    <div class="text-center py-5 bg-sky-200 dark:bg-sky-900 rounded-2xl shadow-lg">
                        OK
                    </div>
                </a>
            </div>
        </div>
        @endforeach
    </div>
    </div>
</div>
</x-app-layout>
