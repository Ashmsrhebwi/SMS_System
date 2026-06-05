<?php

namespace App\Http\Controllers\Api\V1;

use App\Exports\CampaignReportExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\CampaignResource;
use App\Http\Resources\MessageResource;
use App\Jobs\DispatchCampaignJob;
use App\Jobs\SendSmsJob;
use App\Models\Campaign;
use App\Models\Message;
use App\Services\AuditLogger;
use App\Services\SegmentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CampaignController extends Controller
{
    public function __construct(private SegmentService $segmentService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Campaign::class);

        $query = Campaign::with('segment', 'creator')->latest();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $campaigns = $query->paginate($request->integer('per_page', 20));

        return CampaignResource::collection($campaigns)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Campaign::class);

        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'message_body' => 'required|string|max:1600',
            'scheduled_at' => 'nullable|date|after:now',
            'send_now'     => 'boolean',
            'segment_id'   => 'nullable|exists:segments,id',
        ]);

        $campaign = Campaign::create([
            'name'         => $data['name'],
            'message_body' => $data['message_body'],
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'status'       => $data['scheduled_at'] ? 'scheduled' : 'draft',
            'segment_id'   => $data['segment_id'] ?? null,
            'created_by'   => auth()->id(),
        ]);

        AuditLogger::log('create_campaign', $campaign, null, $campaign->only(['name', 'status']));

        if ($request->boolean('send_now')) {
            DispatchCampaignJob::dispatch($campaign->id);
            AuditLogger::log('send_campaign', $campaign, null, ['trigger' => 'send_now']);
        } elseif ($data['scheduled_at'] ?? null) {
            DispatchCampaignJob::dispatch($campaign->id)->delay(Carbon::parse($data['scheduled_at']));
        }

        return (new CampaignResource($campaign->load('segment', 'creator')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Campaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);

        $campaign->load('segment', 'creator');

        $stats = [
            'total'     => $campaign->messages()->count(),
            'pending'   => $campaign->messages()->where('status', 'pending')->count(),
            'queued'    => $campaign->messages()->where('status', 'queued')->count(),
            'sent'      => $campaign->messages()->where('status', 'sent')->count(),
            'delivered' => $campaign->messages()->where('status', 'delivered')->count(),
            'failed'    => $campaign->messages()->whereIn('status', ['failed', 'undelivered'])->count(),
            'clicked'   => $campaign->click_count,
        ];

        $total = max($stats['total'], 1);
        $stats['delivery_rate'] = $stats['total'] > 0 ? round(($stats['delivered'] / $total) * 100, 1) : 0;
        $stats['failure_rate']  = $stats['total'] > 0 ? round(($stats['failed'] / $total) * 100, 1) : 0;
        $stats['click_rate']    = $stats['delivered'] > 0 ? round(($stats['clicked'] / $stats['delivered']) * 100, 1) : 0;
        $stats['total_cost']    = (float) $campaign->messages()->sum('cost');
        $stats['total_segments'] = (int) $campaign->messages()->sum('sms_segments');

        return response()->json([
            'campaign' => new CampaignResource($campaign),
            'stats'    => $stats,
        ]);
    }

    public function update(Request $request, Campaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        if ($campaign->status === 'sending') {
            return response()->json(['message' => 'Cannot edit a campaign that is currently sending.'], 422);
        }

        $data = $request->validate([
            'name'         => 'sometimes|required|string|max:255',
            'message_body' => 'sometimes|required|string|max:1600',
            'scheduled_at' => 'nullable|date|after:now',
            'segment_id'   => 'nullable|exists:segments,id',
        ]);

        $old = $campaign->only(['name', 'message_body', 'status', 'segment_id']);
        $campaign->update($data);
        AuditLogger::log('update_campaign', $campaign, $old, $campaign->fresh()->only(['name', 'status']));

        return (new CampaignResource($campaign->load('segment', 'creator')))->response();
    }

    public function destroy(Campaign $campaign): JsonResponse
    {
        $this->authorize('delete', $campaign);

        if ($campaign->status === 'sending') {
            return response()->json(['message' => 'Cannot delete a campaign that is currently sending.'], 422);
        }

        AuditLogger::log('delete_campaign', $campaign, $campaign->only(['name', 'status']));
        $campaign->delete();

        return response()->json(['message' => 'Campaign deleted.']);
    }

    public function sendNow(Campaign $campaign): JsonResponse
    {
        $this->authorize('send', $campaign);

        if (!in_array($campaign->status, ['draft', 'scheduled'])) {
            return response()->json(['message' => 'Campaign cannot be sent in its current state.'], 422);
        }

        DispatchCampaignJob::dispatch($campaign->id);
        AuditLogger::log('send_campaign', $campaign);

        return response()->json(['message' => 'Campaign is being dispatched.']);
    }

    public function resendFailed(Campaign $campaign): JsonResponse
    {
        $this->authorize('send', $campaign);

        $failed = $campaign->messages()
            ->whereIn('status', ['failed', 'undelivered'])
            ->with(['contact' => fn($q) => $q->withTrashed()])
            ->get();

        if ($failed->isEmpty()) {
            return response()->json(['message' => 'No failed messages to resend.'], 422);
        }

        $resent = 0;
        foreach ($failed as $message) {
            if (!$message->contact || !$message->contact->opted_in) {
                continue;
            }
            $message->update([
                'status'        => 'pending',
                'error_code'    => null,
                'error_message' => null,
                'resend_count'  => $message->resend_count + 1,
            ]);
            SendSmsJob::dispatch($message->id)->delay(now()->addSeconds($resent));
            $resent++;
        }

        AuditLogger::log('resend_failed_messages', $campaign, null, ['resent' => $resent]);

        return response()->json(['message' => "Re-queued {$resent} failed message(s) for delivery.", 'resent' => $resent]);
    }

    public function duplicate(Campaign $campaign): JsonResponse
    {
        $this->authorize('duplicate', $campaign);

        $clone               = $campaign->replicate();
        $clone->name         = $campaign->name . ' (Copy)';
        $clone->status       = 'draft';
        $clone->scheduled_at = null;
        $clone->total_recipients = 0;
        $clone->created_by   = auth()->id();
        $clone->save();

        AuditLogger::log('duplicate_campaign', $clone, null, ['source_id' => $campaign->id]);

        return (new CampaignResource($clone->load('segment', 'creator')))
            ->response()
            ->setStatusCode(201);
    }

    public function exportReport(Campaign $campaign)
    {
        $this->authorize('export', $campaign);
        $filename = 'campaign-' . $campaign->id . '-report-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new CampaignReportExport($campaign), $filename);
    }

    public function messages(Request $request, Campaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);

        $messages = $campaign->messages()
            ->with(['contact' => fn($q) => $q->withTrashed()->select('id', 'name', 'phone'), 'click'])
            ->latest()
            ->paginate($request->integer('per_page', 50));

        return MessageResource::collection($messages)->response();
    }
}
