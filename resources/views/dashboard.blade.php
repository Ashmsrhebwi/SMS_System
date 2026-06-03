<x-app-layout>
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-gray-500 text-sm mt-1">FeRa Clinic — SMS Campaign Overview</p>
        </div>
        <a href="{{ route('campaigns.create') }}"
           class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
            + New Campaign
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Total Contacts</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_contacts']) }}</p>
            <p class="text-xs text-green-600 mt-1">{{ number_format($stats['opted_in']) }} opted in</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Total Campaigns</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_campaigns']) }}</p>
            <p class="text-xs text-orange-500 mt-1">{{ $stats['active_campaigns'] }} active</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Messages Sent</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_messages_sent']) }}</p>
            <p class="text-xs text-green-600 mt-1">{{ number_format($stats['total_delivered']) }} delivered</p>
        </div>
    </div>

    <!-- Recent Campaigns -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Recent Campaigns</h2>
            <a href="{{ route('campaigns.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">View all →</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentCampaigns as $campaign)
            <div class="px-6 py-4 flex items-center justify-between">
                <div>
                    <p class="font-medium text-gray-900">{{ $campaign->name }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $campaign->created_at->format('d M Y') }}</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500">{{ number_format($campaign->total_recipients) }} recipients</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium
                        @if($campaign->status === 'completed') bg-green-100 text-green-700
                        @elseif($campaign->status === 'sending') bg-blue-100 text-blue-700
                        @elseif($campaign->status === 'scheduled') bg-yellow-100 text-yellow-700
                        @elseif($campaign->status === 'draft') bg-gray-100 text-gray-600
                        @else bg-red-100 text-red-700 @endif">
                        {{ ucfirst($campaign->status) }}
                    </span>
                    <a href="{{ route('campaigns.show', $campaign) }}" class="text-sm text-indigo-600 hover:text-indigo-800">View →</a>
                </div>
            </div>
            @empty
            <div class="px-6 py-10 text-center text-gray-400">
                <p>No campaigns yet. <a href="{{ route('campaigns.create') }}" class="text-indigo-600">Create your first campaign →</a></p>
            </div>
            @endforelse
        </div>
    </div>
</div>
</x-app-layout>
