<x-app-layout>
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Campaigns</h1>
        <a href="{{ route('campaigns.create') }}"
           class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
            + New Campaign
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaign</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recipients</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Delivered</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scheduled</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-50">
                @forelse($campaigns as $campaign)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <p class="font-medium text-gray-900">{{ $campaign->name }}</p>
                        <p class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ Str::limit($campaign->message_body, 60) }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                            @if($campaign->status === 'completed') bg-green-100 text-green-700
                            @elseif($campaign->status === 'sending') bg-blue-100 text-blue-700
                            @elseif($campaign->status === 'scheduled') bg-yellow-100 text-yellow-700
                            @elseif($campaign->status === 'draft') bg-gray-100 text-gray-600
                            @else bg-red-100 text-red-700 @endif">
                            {{ ucfirst($campaign->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ number_format($campaign->total_recipients) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ number_format($campaign->delivered_count) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $campaign->scheduled_at ? $campaign->scheduled_at->format('d M Y H:i') : '—' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $campaign->created_at->format('d M Y') }}</td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center gap-2 justify-end">
                            <a href="{{ route('campaigns.show', $campaign) }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">View</a>
                            @if(in_array($campaign->status, ['draft', 'scheduled']))
                                <form method="POST" action="{{ route('campaigns.send-now', $campaign) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-900 text-sm font-medium"
                                        onclick="return confirm('Send this campaign now?')">Send Now</button>
                                </form>
                                <form method="POST" action="{{ route('campaigns.destroy', $campaign) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm"
                                        onclick="return confirm('Delete this campaign?')">Delete</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                        No campaigns yet. <a href="{{ route('campaigns.create') }}" class="text-indigo-600">Create your first →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $campaigns->links() }}
        </div>
    </div>
</div>
</x-app-layout>
