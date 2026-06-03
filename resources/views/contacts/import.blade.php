<x-app-layout>
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Import Contacts</h1>
        <p class="text-sm text-gray-500 mt-1">Upload an Excel (.xlsx/.xls) or CSV file with your contacts.</p>
    </div>

    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('contacts.import.store') }}" enctype="multipart/form-data">
            @csrf

            <!-- File Upload -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select File</label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-indigo-400 transition cursor-pointer"
                     onclick="document.getElementById('fileInput').click()">
                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">Click to upload or drag and drop</p>
                    <p class="text-xs text-gray-400 mt-1">XLSX, XLS, CSV up to 10MB</p>
                    <p id="fileName" class="text-sm text-indigo-600 mt-2 hidden"></p>
                </div>
                <input type="file" id="fileInput" name="file" accept=".xlsx,.xls,.csv" class="hidden"
                       onchange="document.getElementById('fileName').textContent = this.files[0].name; document.getElementById('fileName').classList.remove('hidden')">
            </div>

            <!-- Column Format Guide -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Expected Column Names</p>
                <div class="grid grid-cols-2 gap-2 text-xs text-gray-600">
                    <div><span class="font-mono bg-white border border-gray-200 px-1.5 py-0.5 rounded">name</span> or <span class="font-mono bg-white border border-gray-200 px-1.5 py-0.5 rounded">full_name</span> <span class="text-red-500">*</span></div>
                    <div><span class="font-mono bg-white border border-gray-200 px-1.5 py-0.5 rounded">phone</span> or <span class="font-mono bg-white border border-gray-200 px-1.5 py-0.5 rounded">mobile</span> <span class="text-red-500">*</span></div>
                    <div><span class="font-mono bg-white border border-gray-200 px-1.5 py-0.5 rounded">last_visit</span> (optional)</div>
                    <div><span class="font-mono bg-white border border-gray-200 px-1.5 py-0.5 rounded">notes</span> (optional)</div>
                </div>
                <p class="text-xs text-gray-400 mt-2">UK numbers accepted: <code>07XXXXXXXXX</code>, <code>+447XXXXXXXXX</code>, <code>447XXXXXXXXX</code></p>
            </div>

            <button type="submit"
                    class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-indigo-700 transition w-full">
                Import Contacts
            </button>
        </form>
    </div>
</div>
</x-app-layout>
