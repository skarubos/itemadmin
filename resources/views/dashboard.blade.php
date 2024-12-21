<x-app-layout>
<div class="pt-3 pb-8">
    <div class="min-h-screen max-w-xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 font-sans text-gray-900 dark:text-gray-100 py-12 shadow-sm bg-clip-border rounded-lg">
        <div class="mb-10">
            <p class="text-2xl font-bold text-center">
                <span class="font-light text-xl mr-2">
                    {{ str_pad($user->member_code, 5, '0', STR_PAD_LEFT) }}
                </span>
                {{ $user->name }}<span class="ml-2">様</span>
            </p>
        </div>
        <div class="mb-10">
            <a 
                href="/sales/member/{{ $user['member_code'] }}"
                class="block w-4/5 mx-auto py-10 text-center bg-gray-100 dark:bg-gray-900 text-2xl font-sans drop-shadow-xl bg-clip-border rounded-2xl text-2xl"
            >
                注文履歴
            </a>
        </div>
        <div class="mb-10">
            <a 
                href="/depo/member/{{ $user['member_code'] }}"
                class="block w-4/5 mx-auto py-10 text-center bg-gray-100 dark:bg-gray-900 text-2xl font-sans drop-shadow-xl bg-clip-border rounded-2xl text-2xl"
            >
                預け
            </a>
        </div>
        @if ($user->sub_leader != 0)
        <div class="mb-10">
            <a 
                href="/sub/{{ $user['member_code'] }}"
                class="block w-4/5 mx-auto py-10 text-center bg-gray-100 dark:bg-gray-900 text-2xl font-sans drop-shadow-xl bg-clip-border rounded-2xl text-2xl"
            >
                資格手当
            </a>
        </div>
        @endif
        <p class="mx-auto pt-10 text-center">
        最終更新：{{ \Carbon\Carbon::parse($latest->updated_at)->format('Y年n月j日') }}<br>
        （注文日から数日後に反映されます。）
        </p>
    </div>
    
    </div>
</div>
</x-app-layout>
