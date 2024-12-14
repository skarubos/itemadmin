<x-app-layout>
<div class="pt-3 pb-20">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 font-sans text-gray-900 dark:text-gray-100 px-5 shadow-sm bg-clip-border rounded-lg">
        <div class="p-4">
        <div class="my-4">
            <p class="text-center text-lg font-bold leading-snug tracking-normal antialiased">
                実績
            </p>
            <a href="/refresh_sales">
            </a>
        </div>
        <div class="grid grid-cols-1 divide-y">
            @foreach($users as $user)
            <div class="py-2 font-sans text-xl">
                <a href="/sales_detail/{{ $user['member_code'] }}" class="flex justify-between">
                    <div class="w-7/12">
                        {{ $user['name'] }}
                    </div>
                    @if ($user['sub_leader'] != 0)
                        <div class="">
                            ({{ $user['sub_now'] }})
                        </div>
                    @endif
                    <div class="w-3/12 h-0.5 text-xs text-right">
                        @if(isset($latestTrades[$user->id]))
                            {{ \Carbon\Carbon::parse($latestTrades[$user->id]->date)->format('y/n/j') }}<br>
                            {{ $latestTrades[$user->id]->amount }}
                        @else
                            -
                        @endif
                    </div>
                    <div class="w-2/12 text-right">
                        {{ $user['sales'] }}
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
