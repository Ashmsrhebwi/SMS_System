<x-app-layout>
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Contacts</h1>
        <a href="{{ route('contacts.import') }}"
           class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
            Import from Excel/CSV
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    @if(session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg text-sm">
            <p class="font-medium">Import Complete: {{ $result['imported'] }} imported, {{ $result['skipped'] }} skipped.</p>
            @if(!empty($result['errors']))
                <details class="mt-2">
                    <summary class="cursor-pointer text-xs">Show {{ count($result['errors']) }} issue(s)</summary>
                    <ul class="mt-1 space-y-0.5">
                        @foreach(array_slice($result['errors'], 0, 20) as $error)
                            <li class="text-xs text-blue-700">{{ $error }}</li>
                        @endforeach
                        @if(count($result['errors']) > 20)
                            <li class="text-xs text-gray-500">... and {{ count($result['errors']) - 20 }} more</li>
                        @endif
                    </ul>
                </details>
            @endif
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Opt-In Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Visit</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Added</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-50">
                @forelse($contacts as $contact)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $contact->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500 font-mono">{{ $contact->phone }}</td>
                    <td class="px-6 py-4">
                        <form method="POST" action="{{ route('contacts.toggle-opt-in', $contact) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="px-2 py-0.5 rounded-full text-xs font-medium cursor-pointer transition
                                {{ $contact->opted_in ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}">
                                {{ $contact->opted_in ? '✓ Opted In' : '✗ Opted Out' }}
                            </button>
                        </form>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $contact->last_visit?->format('d M Y') ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $contact->created_at->format('d M Y') }}</td>
                    <td class="px-6 py-4 text-right">
                        <form method="POST" action="{{ route('contacts.destroy', $contact) }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-400 hover:text-red-600 text-xs"
                                onclick="return confirm('Delete this contact?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                        No contacts yet. <a href="{{ route('contacts.import') }}" class="text-indigo-600">Import from Excel →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $contacts->links() }}
        </div>
    </div>
</div>
</x-app-layout>
