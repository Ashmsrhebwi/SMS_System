<x-app-layout>
@section('page-title', 'Contacts')
@section('page-subtitle', 'Manage your patient contact list')

<div class="max-w-7xl mx-auto">

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-7">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Contacts</h2>
            <p class="text-sm text-slate-500 mt-0.5">All registered patients and their opt-in status.</p>
        </div>
        <a href="{{ route('contacts.import') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            Import Contacts
        </a>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="mb-5 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm">
            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="mb-5 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-4 rounded-xl text-sm">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="font-semibold">Import complete:
                    <span class="text-emerald-700">{{ $result['imported'] }} imported</span> &middot;
                    <span class="text-amber-700">{{ $result['skipped'] }} skipped</span>
                </p>
            </div>
            @if(!empty($result['errors']))
                <details class="mt-2">
                    <summary class="cursor-pointer text-xs font-medium text-blue-700 hover:text-blue-900">
                        Show {{ count($result['errors']) }} issue(s)
                    </summary>
                    <ul class="mt-2 space-y-0.5 max-h-32 overflow-y-auto">
                        @foreach(array_slice($result['errors'], 0, 20) as $error)
                            <li class="text-xs text-blue-700 pl-2 border-l-2 border-blue-200">{{ $error }}</li>
                        @endforeach
                        @if(count($result['errors']) > 20)
                            <li class="text-xs text-slate-500 pl-2">... and {{ count($result['errors']) - 20 }} more</li>
                        @endif
                    </ul>
                </details>
            @endif
        </div>
    @endif

    <!-- Contacts Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70">
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Phone</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Opt-In</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Last Visit</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Added</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($contacts as $contact)
                    <tr class="hover:bg-slate-50/50 transition-colors even:bg-slate-50/20">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold
                                    {{ $contact->opted_in ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ strtoupper(substr($contact->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $contact->name)[1] ?? '', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $contact->name }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-mono text-slate-600 bg-slate-50 px-2 py-0.5 rounded-lg border border-slate-100">{{ $contact->phone }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <form method="POST" action="{{ route('contacts.toggle-opt-in', $contact) }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 transition-colors cursor-pointer
                                        {{ $contact->opted_in
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 hover:bg-emerald-100'
                                            : 'bg-slate-100 text-slate-500 ring-slate-200 hover:bg-slate-200' }}">
                                    @if($contact->opted_in)
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Opted In
                                    @else
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Opted Out
                                    @endif
                                </button>
                            </form>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">
                            {{ $contact->last_visit ? $contact->last_visit->format('d M Y') : '—' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">
                            {{ $contact->created_at->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <form method="POST" action="{{ route('contacts.destroy', $contact) }}" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    onclick="return confirm('Delete {{ addslashes($contact->name) }}?')"
                                    class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-red-600 transition-colors px-2 py-1 rounded-lg hover:bg-red-50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center">
                                    <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">No contacts yet</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Import your patient list to get started</p>
                                </div>
                                <a href="{{ route('contacts.import') }}"
                                   class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors mt-1">
                                    Import from Excel
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($contacts->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $contacts->links() }}
        </div>
        @endif
    </div>
</div>
</x-app-layout>
