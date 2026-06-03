<x-app-layout>
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex items-start justify-between mb-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900">{{ $campaign->name }}</h1>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                    @if($campaign->status === 'completed') bg-green-100 text-green-700
                    @elseif($campaign->status === 'sending') bg-blue-100 text-blue-700
                    @elseif($campaign->status === 'scheduled') bg-yellow-100 text-yellow-700
                    @elseif($campaign->status === 'draft') bg-gray-100 text-gray-600
                    @else bg-red-100 text-red-700 @endif">
                    {{ ucfirst($campaign->status) }}
                </span>
            </div>
            <p class="text-sm text-gray-400 mt-1">
                Created {{ $campaign->created_at->format('d M Y H:i') }}
                @if($campaign->scheduled_at) · Scheduled: {{ $campaign->scheduled_at->format('d M Y H:i') }} @endif
            </p>
        </div>
        <div class="flex gap-2">
            @if(in_array($campaign->status, ['draft', 'scheduled']))
                <form method="POST" action="{{ route('campaigns.send-now', $campaign) }}">
                    @csrf
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700"
                        onclick="return confirm('Send now?')">Send Now</button>
                </form>
            @endif
            <a href="{{ route('campaigns.export', $campaign) }}"
               class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50">
                Export Excel
            </a>
            <a href="{{ route('campaigns.index') }}" class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50">
                ← Back
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    <!-- Message Body Preview -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5">
        <p class="text-xs text-gray-500 uppercase font-medium mb-2">Message Template</p>
        <p class="text-sm text-gray-800 whitespace-pre-line leading-relaxed">{{ $campaign->message_body }}</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total']) }}</p>
            <p class="text-xs text-gray-500 mt-1">Total</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl font-bold text-yellow-600">{{ number_format($stats['pending']) }}</p>
            <p class="text-xs text-gray-500 mt-1">Pending</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl font-bold text-blue-600">{{ number_format($stats['queued']) }}</p>
            <p class="text-xs text-gray-500 mt-1">Queued</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl font-bold text-indigo-600">{{ number_format($stats['sent']) }}</p>
            <p class="text-xs text-gray-500 mt-1">Sent</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl font-bold text-green-600">{{ number_format($stats['delivered']) }}</p>
            <p class="text-xs text-gray-500 mt-1">Delivered</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl font-bold text-red-600">{{ number_format($stats['failed']) }}</p>
            <p class="text-xs text-gray-500 mt-1">Failed</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl font-bold text-purple-600">{{ number_format($stats['clicked']) }}</p>
            <p class="text-xs text-gray-500 mt-1">Clicked</p>
        </div>
    </div>

    <!-- Rate indicators -->
    @if($stats['total'] > 0)
    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 mb-1">Delivery Rate</p>
            @php $deliveryRate = $stats['total'] > 0 ? round(($stats['delivered'] / max($stats['total'],1)) * 100, 1) : 0; @endphp
            <div class="flex items-center gap-2">
                <div class="flex-1 bg-gray-200 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ $deliveryRate }}%"></div>
                </div>
                <span class="text-sm font-semibold text-green-700">{{ $deliveryRate }}%</span>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 mb-1">Click Rate (of delivered)</p>
            @php $clickRate = $stats['delivered'] > 0 ? round(($stats['clicked'] / $stats['delivered']) * 100, 1) : 0; @endphp
            <div class="flex items-center gap-2">
                <div class="flex-1 bg-gray-200 rounded-full h-2">
                    <div class="bg-purple-500 h-2 rounded-full" style="width: {{ min($clickRate, 100) }}%"></div>
                </div>
                <span class="text-sm font-semibold text-purple-700">{{ $clickRate }}%</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Messages Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold text-gray-900">Message Details</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Error</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sent At</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Delivered At</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clicked</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($messages as $message)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $message->contact->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 font-mono">{{ $message->contact->phone }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                @if($message->status === 'delivered') bg-green-100 text-green-700
                                @elseif($message->status === 'sent') bg-blue-100 text-blue-700
                                @elseif($message->status === 'queued') bg-yellow-100 text-yellow-700
                                @elseif(in_array($message->status, ['failed','undelivered'])) bg-red-100 text-red-700
                                @else bg-gray-100 text-gray-600 @endif">
                                {{ ucfirst($message->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-red-600">
                            @if($message->error_code)
                                {{ $message->error_code }}: {{ Str::limit($message->error_message, 40) }}
                            @else—@endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $message->sent_at?->format('d M H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $message->delivered_at?->format('d M H:i') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if($message->click?->click_count > 0)
                                <span class="text-xs text-purple-700 font-medium">
                                    ✓ {{ $message->click->click_count }}x
                                    <span class="text-gray-400 font-normal">({{ $message->click->first_clicked_at?->format('d M H:i') }})</span>
                                </span>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-6 py-10 text-center text-gray-400">No messages yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $messages->links() }}
        </div>
    </div>
</div>
</x-app-layout>
