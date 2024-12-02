<x-app-layout>
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 font-sans text-gray-900 dark:text-gray-100 px-5 shadow-sm bg-clip-border rounded-lg">
        <p class="text-base mb-4">{{ $user->member_code }}</p>
        <p class="text-xl font-bold mb-4">{{ $user->name }}</p>
        <table class="min-w-full border border-gray-200">
            <thead class="bg-gray-200 dark:bg-gray-800 dark:text-gray-100">
                <tr>
                    <th class="py-2 px-4 border-b">月</th>
                    <th class="py-2 px-4 border-b">セット</th>
                </tr>
            </thead>
            <tbody>
                @foreach($details as $index => $sales)
                <tr class="border-b">
                    <td class="py-2 px-4 text-center">{{ $index + 1 }}月</td>
                    <td class="py-2 px-4 text-center">{{ $sales > 0 ? $sales : '-' }}</td>
                </tr>
                @endforeach
                <tr class="border-t">
                    <td class="py-2 px-4 text-center font-bold">合計</td>
                    <td class="py-2 px-4 text-center font-bold">{{ array_sum($details) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    </div>
</div>
</x-app-layout>