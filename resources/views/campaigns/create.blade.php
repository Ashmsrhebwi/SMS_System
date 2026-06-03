<x-app-layout>
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">New Campaign</h1>
        <p class="text-sm text-gray-500 mt-1">{{ number_format($eligibleCount) }} eligible contacts (opted-in, not unsubscribed)</p>
    </div>

    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('campaigns.store') }}" id="campaignForm">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">
            <!-- Campaign Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Campaign Name</label>
                <input type="text" name="name" value="{{ old('name') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       placeholder="e.g. Summer Checkup Reminder">
            </div>

            <!-- Message Body -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Message Body</label>
                <p class="text-xs text-gray-400 mb-2">Use <code class="bg-gray-100 px-1 rounded">{name}</code> to personalise with contact name.
                   Use <code class="bg-gray-100 px-1 rounded">{tracking_url}</code> for a trackable link.</p>
                <textarea name="message_body" id="messageBody" rows="5"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    placeholder="Hi {name}, this is FeRa Clinic. Book your appointment: {tracking_url}"
                    oninput="updatePreview()">{{ old('message_body') }}</textarea>

                <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                    <span id="charCount">0 characters</span>
                    <span id="segmentCount">1 segment (160 chars)</span>
                </div>
            </div>

            <!-- Message Preview -->
            <div id="previewSection" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Message Preview</label>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="bg-white rounded-lg px-4 py-3 shadow-sm inline-block max-w-xs text-sm text-gray-800 border border-gray-100">
                        <p class="text-xs text-indigo-600 font-medium mb-1">FeRa Clinic</p>
                        <p id="previewText" class="leading-relaxed"></p>
                    </div>
                </div>
            </div>

            <!-- Scheduling -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">When to Send</label>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="send_timing" value="now" checked class="text-indigo-600">
                        <span class="text-sm">Send immediately</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="send_timing" value="later" class="text-indigo-600" id="scheduleRadio">
                        <span class="text-sm">Schedule for later</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="send_timing" value="draft" class="text-indigo-600">
                        <span class="text-sm">Save as draft</span>
                    </label>
                </div>

                <div id="scheduleInput" class="hidden mt-3">
                    <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}"
                           class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" id="submitBtn"
                    class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                Send Campaign
            </button>
            <a href="{{ route('campaigns.index') }}" class="border border-gray-300 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
function updatePreview() {
    const body = document.getElementById('messageBody').value;
    const charCount = document.getElementById('charCount');
    const segmentCount = document.getElementById('segmentCount');
    const previewSection = document.getElementById('previewSection');
    const previewText = document.getElementById('previewText');

    const len = body.length;
    charCount.textContent = len + ' characters';

    // SMS segment calculation (GSM-7: 160/segment, multi-part: 153/segment)
    let segs = 1;
    if (len > 160) segs = Math.ceil(len / 153);
    segmentCount.textContent = segs + ' segment' + (segs > 1 ? 's' : '') + ' (' + (segs === 1 ? '160' : '153') + ' chars/segment)';

    if (len > 0) {
        previewSection.classList.remove('hidden');
        previewText.textContent = body.replace('{name}', 'John').replace('{tracking_url}', 'https://fera.clinic/r/abc123');
    } else {
        previewSection.classList.add('hidden');
    }
}

// Schedule radio toggle
document.querySelectorAll('input[name="send_timing"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const scheduleInput = document.getElementById('scheduleInput');
        const submitBtn = document.getElementById('submitBtn');
        const sendNowInput = document.querySelector('input[name="send_now"]');

        if (this.value === 'later') {
            scheduleInput.classList.remove('hidden');
            submitBtn.textContent = 'Schedule Campaign';
        } else if (this.value === 'draft') {
            scheduleInput.classList.add('hidden');
            submitBtn.textContent = 'Save as Draft';
        } else {
            scheduleInput.classList.add('hidden');
            submitBtn.textContent = 'Send Campaign';
        }
    });
});

document.getElementById('campaignForm').addEventListener('submit', function(e) {
    const timing = document.querySelector('input[name="send_timing"]:checked').value;
    if (timing === 'now') {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'send_now';
        input.value = '1';
        this.appendChild(input);

        if (!confirm('Send this campaign to all eligible contacts now?')) {
            e.preventDefault();
        }
    }
});

updatePreview();
</script>
</x-app-layout>
