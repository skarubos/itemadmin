<x-app-layout>
<div class="sm:flex sm:px-3 lg:px-6">
<div class="max-w-7xl mx-auto pt-6 sm:w-1/3 sm:px-2 lg:px-4">
    <div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 overflow-hidden pb-5 shadow-sm sm:rounded-lg sm:pb-10 lg:px-5">
        <div class="p-6">
            {{ __("移動登録") }}
        </div>
        <form action="/trade/create/idou" method="POST" enctype="multipart/form-data" class="px-7 pb-5 relative">
            @csrf
            <input type="hidden"  name="file" value="">
            <x-primary-button class="float-right px-10">
                登録ページへ
            </x-primary-button>
        </form>
    </div>
</div>

<div class="max-w-7xl mx-auto pt-6 sm:w-2/3 sm:px-2 lg:px-4">
    <div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 overflow-hidden pb-5 shadow-sm sm:rounded-lg sm:pb-10 lg:px-5">
        <div class="p-6">
            {{ __("Upload Excel") }}
        </div>
        <form action="/trade/create" method="POST" enctype="multipart/form-data" class="px-7 pb-5 relative">
            @csrf
            <div class="border border-dashed border-gray-500 dark:bg-gray-900/30 relative mb-5 rounded">
                <input type="file"  name="file" required multiple onchange="updateFileName(this)" class="cursor-pointer relative block opacity-0 w-full h-full p-16 z-50">
                <div class="text-center absolute top-0 right-0 left-0 m-auto">
                    <p id="upload-caption" class="py-10  text-gray-900/50 dark:text-gray-100/50">
                        Drop files anywhere to upload
                        <br/>or<br/>Select Files
                    </p>
                    <p id="file-name-display" class="hidden rounded-lg py-16">testsheet.xlm</p>
                </div>
            </div>
            <x-primary-button class="float-right px-10">
                Upload
            </x-primary-button>
        </form>
    </div>
</div>
</div>

<script>
    function updateFileName(input) {
        let fileName = input.files[0].name;
        document.getElementById('file-name-display').innerText = fileName;
        document.getElementById('file-name-display').classList.remove('hidden');
        document.getElementById('upload-caption').classList.add('hidden');
    }
</script>
</x-app-layout>
