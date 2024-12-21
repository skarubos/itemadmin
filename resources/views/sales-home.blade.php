<x-app-layout>
<div class="pt-3 pb-20">
    <div class="max-w-xl mx-auto lg:px-8">
    <div class="bg-white dark:bg-gray-800 font-sans text-gray-900 dark:text-gray-100 px-6 py-6 shadow-sm bg-clip-border rounded-lg">
        <div class="my-4">
            <p class="text-center text-xl font-bold leading-snug tracking-normal antialiased">
                実績
            </p>
            <p class="mx-auto pt-1 text-center">
                最終更新：{{ \Carbon\Carbon::parse($latest->updated_at)->format('Y年n月j日') }}
            </p>
        </div>
        <div class="grid grid-cols-1 divide-y">
            @foreach($users as $user)
            <div class="py-2 font-sans text-xl">
                <a href="/sales/member/{{ $user['member_code'] }}" class="">
                <div class="flex justify-between">
                    <div class="">
                        {{ $user['name'] }}
                    </div>
                    <div class="flex">
                        <div class="text-xs text-right">
                            @if(isset($latestTrades[$user->id]))
                                {{ \Carbon\Carbon::parse($latestTrades[$user->id]->date)->format('y/n/j') }}<br>
                                {{ $latestTrades[$user->id]->amount }}
                            @else
                                -
                            @endif
                        </div>
                        <div class="min-w-10 text-right pl-0 sm:pl-3">
                            {{ $user['sales'] }}
                        </div>
                    </div>
                </div>
                </a>
                @if ($user['sub_leader'] != 0)
                <a href="/sub/{{ $user['member_code'] }}" class="">
                    <div class="text-lg text-center py-0.5 mx-auto bg-gray-100 dark:bg-gray-900 rounded-2xl">
                        {{ $user['sub_now'] }}
                        （{{ $user->sub_number / 100 }}級）
                    </div>
                </a>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    </div>
</div>
</x-app-layout>
