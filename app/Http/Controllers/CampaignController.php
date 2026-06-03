<?php

namespace App\Http\Controllers;

use App\Exports\CampaignReportExport;
use App\Jobs\DispatchCampaignJob;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\OptOut;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::latest()->paginate(20);
        return view('campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        $eligibleCount = Contact::where('opted_in', true)
            ->whereNotIn('phone', OptOut::pluck('phone'))
            ->count();

        return view('campaigns.create', compact('eligibleCount'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'message_body' => 'required|string|max:1600',
            'scheduled_at' => 'nullable|date|after:now',
            'send_now' => 'boolean',
        ]);

        $campaign = Campaign::create([
            'name' => $data['name'],
            'message_body' => $data['message_body'],
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'status' => $data['scheduled_at'] ? 'scheduled' : 'draft',
        ]);

        if ($request->boolean('send_now')) {
            DispatchCampaignJob::dispatch($campaign->id);
            return redirect()->route('campaigns.show', $campaign)
                ->with('success', 'Campaign is being dispatched!');
        }

        if ($data['scheduled_at']) {
            DispatchCampaignJob::dispatch($campaign->id)
                ->delay(\Carbon\Carbon::parse($data['scheduled_at']));
            return redirect()->route('campaigns.show', $campaign)
                ->with('success', 'Campaign scheduled successfully.');
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign saved as draft.');
    }

    public function show(Campaign $campaign)
    {
        $messages = $campaign->messages()
            ->with(['contact', 'click'])
            ->latest()
            ->paginate(50);

        $stats = [
            'total' => $campaign->messages()->count(),
            'pending' => $campaign->messages()->where('status', 'pending')->count(),
            'queued' => $campaign->messages()->where('status', 'queued')->count(),
            'sent' => $campaign->messages()->where('status', 'sent')->count(),
            'delivered' => $campaign->messages()->where('status', 'delivered')->count(),
            'failed' => $campaign->messages()->whereIn('status', ['failed', 'undelivered'])->count(),
            'clicked' => $campaign->click_count,
        ];

        return view('campaigns.show', compact('campaign', 'messages', 'stats'));
    }

    public function sendNow(Campaign $campaign)
    {
        if (!in_array($campaign->status, ['draft', 'scheduled'])) {
            return back()->with('error', 'Campaign cannot be sent in its current state.');
        }

        DispatchCampaignJob::dispatch($campaign->id);
        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign is being dispatched!');
    }

    public function exportReport(Campaign $campaign)
    {
        $filename = 'campaign-' . $campaign->id . '-report-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new CampaignReportExport($campaign), $filename);
    }

    public function destroy(Campaign $campaign)
    {
        if ($campaign->status === 'sending') {
            return back()->with('error', 'Cannot delete a campaign that is currently sending.');
        }

        $campaign->delete();
        return redirect()->route('campaigns.index')->with('success', 'Campaign deleted.');
    }
}
