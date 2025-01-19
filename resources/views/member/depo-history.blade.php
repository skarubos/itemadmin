<x-app-layout>
<div class="pt-3 pb-8">
    <div class="sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 font-sans text-gray-900 dark:text-gray-100 px-5 shadow-sm bg-clip-border rounded-lg">
        <div class="pt-6">
        <div class="mb-4">
            <p class="text-center text-xl font-bold leading-snug tracking-normal antialiased">
                入出庫履歴（<spaan class="font-normal"> {{ $user->name }} </span>）<br>
                <span class="text-base">現在預け合計: </span>
                <span class="text-xl font-bold">{{ $user->depo_status }}</span>
                <span class="text-base">セット</span>
            </p>
        </div>
        <div class="overflow-x-auto grid grid-cols-1 divide-y">
        <table class="text-xs border border-gray-200">
            <thead class="pt-5 bg-gray-200 dark:bg-gray-800 dark:text-gray-100">
                <tr>
                    <th class="text-left min-w-32 max-w-56 py-2 px-2 border-b"></th>
                @foreach($tradings as $trading)
                    <th class="py-2 px-1 border-b">
                        {{ \Carbon\Carbon::parse($trading->date)->format('Y') }}<br>
                        {{ \Carbon\Carbon::parse($trading->date)->format('n/j') }}
                    </th>
                @endforeach
                    <th class="py-2 px-2 border-b">現在</th>
                </tr>
                <tr>
                    <th class="text-right min-w-32 max-w-56 py-2 px-2 border-b">合計</th>
                @foreach($tradings as $trading)
                    @php // 「預け出し」の時は負の値に変更
                        $tradeType = $trading->trade_type;
                        $displayAmount = ($tradeType == 21 || $tradeType == 121) ? -$trading->amount : $trading->amount;
                    @endphp
                    <th class="max-w-10 py-2 px-2 border-b">{{ $displayAmount }}</th>
                @endforeach
                    <th class="max-w-10 py-2 px-2 border-b">{{ $user->depo_status }}</th>
                </tr>
            </thead>
            <tbody>
            @php $i = 0; @endphp
            @foreach($amountsSelected as $amounts)
                <tr class="border-b">
                    <td class="min-w-32 max-w-56 py-2 px-2">{{ $products[$i] }}</td>
                @foreach($amounts as $index => $amount)
                    @php // 「預け出し」の時は負の値に変更
                        if ($index < count($tradings)) {
                            $tradeType = $tradings[$index]->trade_type;
                        } else {
                            $tradeType = 10;
                        }
                        $displayAmount = ($tradeType == 21 || $tradeType == 121) ? -$amount : $amount;
                    @endphp
                    <td class="max-w-10 py-2 px-2 text-center">{{ $amount == 0 ? '-' : $displayAmount }}</td>
                @endforeach
                </tr>
                @php $i++; @endphp
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
    </div>
</div>
</x-app-layout>




