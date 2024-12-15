<x-app-layout>
<div class="pt-3 pb-8">
    <div class="w-full min-h-screen max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 font-sans text-gray-900 dark:text-gray-100 py-16 shadow-sm bg-clip-border rounded-lg">
        <div class="">
            <a 
                href="/sales_detail/{{ $user['member_code'] }}"
                class="block w-4/5 mx-auto py-10 text-center bg-gray-100 dark:bg-gray-900 text-2xl font-sansdrop-shadow-xl bg-clip-border rounded-2xl text-2xl"
            >
                注文履歴
            </a>
        </div>
        <div class="h-10">
        </div>
        <div class="">
            <a 
                href="/depo_detail/{{ $user['member_code'] }}"
                class="block w-4/5 mx-auto py-10 text-center bg-gray-100 dark:bg-gray-900 text-2xl font-sansdrop-shadow-xl bg-clip-border rounded-2xl text-2xl"
            >
                預け
            </a>
        </div>
        <p class="mx-auto pt-20 text-center">
        最終更新：{{ \Carbon\Carbon::parse($latest->updated_at)->format('Y年n月j日') }}
        </p>
    </div>
    
    </div>
</div>
</x-app-layout>
