<x-app-layout>
@php
    $now = '2025-01';
@endphp
<div id="swipeArea" class="pb-28">
    <div class="flex justify-between py-3 px-5 font-bold opacity-40">
        <div class="">《 前月</div>
        <div class="">次月 》</div>
    </div>
    @foreach($data['totals'] as $month => $total)
    <?php $users = $data['monthlySales'][$month]; ?>
    <div class="month-content {{ $loop->last ? '' : 'hidden' }} sm:w-1/3 sm:px-2 lg:px-4">
        <div class="text-center text-xl py-6 px-4 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 overflow-hidden shadow-sm sm:rounded-lg lg:px-5">
            <div class="pb-3">
                <span class="font-bold text-2xl pr-1">{{ substr($month, 0, 4) }}</span>年
                <span class="font-bold text-2xl pl-2 pr-1">{{ substr($month, -2, 1) == '0' ? substr($month, -1) : substr($month, -2) }}</span>月
            </div>
            <div class="mb-5 py-5 bg-gray-100 dark:bg-gray-900 rounded-2xl">
                仕入：{{ $total[1] }}<span class="text-base"> セット</span><br>
                営業所：{{ $total[0] }}<span class="text-base"> セット</span>
                <div class="pt-2 text-lg">
                    （合計振込）{{number_format(($total[0] + $total[1]) * 5900) }}<span class="text-base"> 円</span><br>
                    （会社振込）{{number_format(($total[0] + $total[1]) * 4400) }}<span class="text-base"> 円</span><br>
                    （管理収入）{{number_format(($total[0]) * 1800) }}<span class="text-base"> 円</span>
                </div>
            </div>
            @foreach($users as $name => $amount)
            <div class="py-3">
                <div class="">{{ $name . '　　　' . $amount}}<span class="text-base"> セット</span></div>
                <div class="text-lg">
                    （振込）{{number_format($amount * 5900) }}<span class="text-base"> 円</span><br>
                    （収入）{{number_format($amount * 1800) }}<span class="text-base"> 円</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>

<script>
    
document.addEventListener('DOMContentLoaded', () => {
    const monthContents = document.querySelectorAll('.month-content');
    let currentIndex = 0;

    const swipeArea = document.getElementById('swipeArea');
    const hammer = new Hammer(swipeArea);

    hammer.on('swipeleft', () => {
        showNextMonth();
    });

    hammer.on('swiperight', () => {
        showPreviousMonth();
    });

    function showNextMonth() {
        monthContents[currentIndex].classList.add('hidden');
        currentIndex = (currentIndex + 1) % monthContents.length;
        monthContents[currentIndex].classList.remove('hidden');
    }

    function showPreviousMonth() {
        monthContents[currentIndex].classList.add('hidden');
        currentIndex = (currentIndex - 1 + monthContents.length) % monthContents.length;
        monthContents[currentIndex].classList.remove('hidden');
    }
});

</script>
</x-app-layout>
