<x-app-layout>
<div class="pt-3 pb-8">
    <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 font-sans text-gray-900 dark:text-gray-100 p-6 shadow-sm bg-clip-border rounded-lg">
        <div class="">
        <div class="flex mb-4">
            <div class="w-4/6 ">
                <p class="text-center text-2xl font-bold leading-snug tracking-normal antialiased">
                    {{ str_replace('　', ' ', $user->name) }} <br> <span class="text-base">預け:</span> {{ $user->depo_status }} <span class="text-base">セット</span>
                </p>
            </div>
            <div class="w-2/6">
                <a href="/depo/member/{{ $user->member_code }}/history">
                    <x-primary-button class="w-full items-center justify-center px-3">
                        履歴を<br>表示
                    </x-primary-button>
                </a>
            </div>
        </div>
        <div class="grid grid-cols-1 divide-y">
            @foreach($details as $detail)
            <div class="flex py-2 font-sans text-xl">
                <div class="w-5/6 mt-0.5">
                    {{ str_replace('　', ' ', $detail->product->name) }}
                </div>
                <div class="w-2/12 text-2xl text-right">
                    {{ $detail->amount }}
                </div>
            </div>
            @endforeach
        </div>
        </div>
    </div>
    </div>
</div>
</x-app-layout>
