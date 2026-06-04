<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Click;
use App\Models\Message;
use App\Models\Segment;
use App\Services\ActivityLogger;
use App\Services\SegmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class DispatchCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(private int $campaignId) {}

    public function handle(SegmentService $segmentService): void
    {
        $campaign = Campaign::findOrFail($this->campaignId);

        if (!in_array($campaign->status, ['draft', 'scheduled'])) {
            return;
        }

        $campaign->update(['status' => 'sending']);

        $segment = $campaign->segment_id ? Segment::find($campaign->segment_id) : null;
        $contacts = $segmentService->getEligibleContacts($segment);

        $campaign->update(['total_recipients' => $contacts->count()]);

        $delaySeconds = (int) config('sms.rate_limit_delay', 1);
        $optOutText   = config('sms.opt_out_text', '');

        $index = 0;
        foreach ($contacts as $contact) {
            $personalizedBody = $this->personalizeMessage(
                $campaign->message_body,
                $contact->name,
                $optOutText,
                $campaign->id,
                $contact->id
            );

            $message = Message::create([
                'campaign_id'  => $campaign->id,
                'contact_id'   => $contact->id,
                'status'       => 'pending',
                'message_body' => $personalizedBody,
            ]);

            $trackingToken = Str::random(16);
            $targetUrl     = config('sms.whatsapp_url', 'https://wa.me/');

            Click::create([
                'message_id'  => $message->id,
                'token'       => $trackingToken,
                'target_url'  => $targetUrl,
                'click_count' => 0,
            ]);

            $trackingUrl = route('track.click', ['token' => $trackingToken]);
            $updatedBody = str_replace('{tracking_url}', $trackingUrl, $personalizedBody);
            if ($updatedBody !== $personalizedBody) {
                $message->update(['message_body' => $updatedBody]);
            }

            ActivityLogger::addedToCampaign($contact->id, $campaign);

            SendSmsJob::dispatch($message->id)
                ->delay(now()->addSeconds($index * $delaySeconds));

            $index++;
        }
    }

    private function personalizeMessage(
        string $body,
        string $contactName,
        string $optOutText,
        int $campaignId,
        int $contactId
    ): string {
        $personalized = str_replace('{name}', $contactName, $body);

        if ($optOutText) {
            $optOutUrl  = route('optout.form', ['campaign' => $campaignId, 'contact' => $contactId]);
            $optOutLine = str_replace('{opt_out_url}', $optOutUrl, $optOutText);
            $personalized .= "\n" . $optOutLine;
        }

        return $personalized;
    }
}
