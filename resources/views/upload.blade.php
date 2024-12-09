<x-app-layout>

@if ($errors->any())
    <div class="block bg-red-500 text-white p-10 py-2">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- 書き込み成功メッセージ -->
@if (session('success'))
    <div class="block bg-green-500 text-white p-10 py-2">
        {{ session('success') }}
    </div>
@endif

<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 overflow-hidden pb-5 shadow-sm sm:rounded-lg">
            <div class="p-6">
                {{ __("Upload Page") }}
            </div>
            <form action="/upload_check" method="POST" enctype="multipart/form-data" class="px-7 pb-5 relative">
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
