<x-app-layout>
<div class="pt-3 pb-8">
    <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 font-sans text-gray-900 text-xl dark:text-gray-100 p-8 shadow-sm bg-clip-border rounded-lg">
        <div class="text-center mb-6">
            <p class="text-2xl py-1">お取引一覧</p>
                （{{ $user->name }}）
        </div>
        @foreach ($groupedTradings as $year => $tradings)
        <div class="text-center font-bold p-2">
            {{ $year }}年
        </div>
        @foreach($tradings as $trading)
        <div class="mb-2">
            <a 
                href="/trade/{{ $user->member_code }}/{{ $trading->id }}"
                class="flex justify-between max-w-72 mx-auto py-2 px-8 text-center bg-gray-100 dark:bg-gray-900 font-sans shadow-md bg-clip-border rounded-3xl"
            >
            <span class="text-lg">
                {{ \Carbon\Carbon::parse($trading->date)->format('n月j日') }} 
            </span>
            <span class="text-lg">
                ({{ $trading->tradeType->name }}) 
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