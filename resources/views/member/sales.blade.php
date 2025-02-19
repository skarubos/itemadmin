<x-app-layout>
<div class="pt-3 pb-8">
    <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 font-sans text-gray-900 dark:text-gray-100 p-8 shadow-sm bg-clip-border rounded-lg">
        <div class="flex mb-6">
            <div class="w-4/6">
                <div class="mb-2 text-center">
                    <div class="inline-block text-2xl font-bold align-bottom">{{ $user->name }}</div>
                </div>
                <div class="text-center">
                    <p class="text-xl ">年間：<span class="text-2xl font-bold">{{ $user->sales }}</span><span class="text-base pl-0.5">セット</span></p>
                    <p class="text-lg">（現在預け：{{ $user->depo_status }}）</p>
                </div>
            </div>
            <div class="w-2/6 content-center">
                <a href="/sales/member/{{ $user->member_code }}/list">
                    <x-primary-button class="w-full items-center justify-center px-3">
                        お取引<br>一覧
                    </x-primary-button>
                </a>
            </div>
        </div>
        <table class="min-w-40 mx-auto border border-gray-200">
            <thead class="pt-5 bg-gray-200 dark:bg-gray-800 dark:text-gray-100">
                <tr>
                    <th class="py-2 px-4 border-b"></th>
                    @foreach($data['years'] as $year)
                    <th class="py-2 px-4 border-b">{{ $year }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($data['yearlySales'] as $index => $monthlySales)
                <tr class="border-b">
                    <td class="py-2 px-4 text-center">{{ $index + 1 }}月</td>
                    @foreach($monthlySales as $sales)
                    <td class="py-2 px-4 text-center">{{ $sales === 0 ? '-' : ($sales ?? '') }}</td>
                    @endforeach
                </tr>
                @endforeach
                <tr class="border-t">
                    <td class="py-2 px-4 text-center font-bold">合計</td>
                    @foreach($data['totals'] as $total)
                    <td class="py-2 px-4 text-center font-bold">{{ $total }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
    </div>
</div>
</x-app-layout>