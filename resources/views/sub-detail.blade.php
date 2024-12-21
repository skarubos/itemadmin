<x-app-layout>
<div class="pt-3 pb-8">
    <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 font-sans text-gray-900 text-xl dark:text-gray-100 p-8 shadow-sm bg-clip-border rounded-lg">
        <div class="mb-5 text-center">
            <div class="inline-block text-xl bg-gradient-to-br from-white to-orange-300 border border-orange-300 dark:bg-gray-900 dark:from-orange-200 dark:to-orange-500 border dark:border-orange-600 dark:text-gray-900 px-5 py-1 rounded-3xl">
                {{ $user->sub_number / 100 }}
                <span class="text-lg">級</span>
            </div>
            <div class="inline-block text-2xl font-bold ml-5 align-bottom">{{ $user->name }}</div>
        </div>
        <div class="text-2xl py-5 mb-2 text-center bg-gray-100 dark:bg-gray-900 rounded-2xl">
            <p class="">資格手当：<span class="font-bold text-3xl pr-1">{{ $user->sub_now }}</span>円</p>
            <p class="text-base">（{{ \Carbon\Carbon::parse($currentDate)->format('n月j日') }}現在）</p>
        </div>
        <div class="text-center text-base pt-8">
            傘下営業所の取引一覧（過去6ヵ月）
        </div>
        @foreach($groupTradings as $index => $tradings)
        <div class="text-xl text-center pt-5">
            {{ $groupMembers[$index]->name }}
            （<span class="text-lg pr-1">計</span>{{ $tradings->sum('amount') }}<span class="text-lg">セット</span>）
        </div>
        @foreach($tradings as $trading)
            <div class="mb-2">
                <a 
                    href="/trade/{{ $groupMembers[$index]->member_code }}/{{ $trading['id'] }}"
                    class="block max-w-72 mx-auto py-2 text-center bg-gray-100 dark:bg-gray-900 font-sans shadow-md bg-clip-border rounded-3xl"
                >
                <span class="text-lg">
                    {{ \Carbon\Carbon::parse($trading->date)->format('Y年n月j日') }} ： 
                </span>
                <span class="font-bold">
                    {{ $trading->amount }}
                </span>
                </a>
            </div>
        @endforeach
        @endforeach
    </div>
    </div>
</div>
</x-app-layout>