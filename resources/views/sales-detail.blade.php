<x-app-layout>
<div class="pt-3 pb-8">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 font-sans text-gray-900 dark:text-gray-100 px-5 pb-8 shadow-sm bg-clip-border rounded-lg">
        <p class="pt-6 pb-2 text-2xl font-bold text-center">
            <span class="font-light text-xl mr-2">
                {{ str_pad($data['user']->member_code, 5, '0', STR_PAD_LEFT) }}
            </span>
            {{ $data['user']->name }}
        </p>
        <div class="text-xl h-8 mb-2 text-center">
            <p class="inline-block ml-5">預け：{{ $data['user']->depo_status }}</p>
            <p class="inline-block ml-5">年間：{{ $data['user']->sales }}</p>
        </div>
        <table class="min-w-40 mx-auto border border-gray-200">
            <thead class="pt-5 bg-gray-200 dark:bg-gray-800 dark:text-gray-100">
                <tr>
                    <th class="py-2 px-4 border-b">2024</th>
                    <th class="py-2 px-4 border-b">セット</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['details'] as $index => $sales)
                <tr class="border-b">
                    <td class="py-2 px-4 text-center">{{ $index + 1 }}月</td>
                    <td class="py-2 px-4 text-center">{{ $sales > 0 ? $sales : '-' }}</td>
                </tr>
                @endforeach
                <tr class="border-t">
                    <td class="py-2 px-4 text-center font-bold">合計</td>
                    <td class="py-2 px-4 text-center font-bold">{{ array_sum($data['details']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    </div>
</div>
</x-app-layout>