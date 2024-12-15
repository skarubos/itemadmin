<x-app-layout>
<div class="pt-3 pb-8">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 font-sans text-gray-900 dark:text-gray-100 py-16 shadow-sm bg-clip-border rounded-lg">
        <div class="mb-10 items-center">
            <a href="/sales_detail/{{ $user['member_code'] }}">
            <div class="w-2/3 mx-auto py-8 text-center bg-gray-100 dark:bg-gray-900 font-sans text-gray-900 dark:text-gray-100 drop-shadow-xl bg-clip-border rounded-2xl text-black text-2xl ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white">
                注文履歴
            </div>
            </a>
        </div>
        <div class="mb-10 items-center">
            <a href="/depo_detail/{{ $user['member_code'] }}">
            <div class="w-2/3 mx-auto py-8 text-center bg-gray-100 dark:bg-gray-900 font-sans text-gray-900 dark:text-gray-100 drop-shadow-xl bg-clip-border rounded-2xl text-black text-2xl ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white">
                預け
            </div>
            </a>
        </div>
        <p class="mx-auto pt-10 text-center">
        最終更新：{{ \Carbon\Carbon::parse($latest->updated_at)->format('Y年n月j日') }}
        </p>
    </div>
    
    </div>
</div>
</x-app-layout>
