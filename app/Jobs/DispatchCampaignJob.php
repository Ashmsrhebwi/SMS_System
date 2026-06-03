<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Click;
use App\Models\Contact;
use App\Models\Message;
use App\Models\OptOut;
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

    public function handle(): void
    {
        $campaign = Campaign::findOrFail($this->campaignId);

        if (!in_array($campaign->status, ['draft', 'scheduled'])) {
            return;
        }

        $campaign->update(['status' => 'sending']);

        $optOutPhones = OptOut::pluck('phone')->toArray();

        $contacts = Contact::where('opted_in', true)
            ->whereNotIn('phone', $optOutPhones)
            ->get();

        $campaign->update(['total_recipients' => $contacts->count()]);

        $delaySeconds = (int) config('sms.rate_limit_delay', 1);
        $optOutText = config('sms.opt_out_text', '');

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
                'campaign_id' => $campaign->id,
                'contact_id' => $contact->id,
                'status' => 'pending',
                'message_body' => $personalizedBody,
            ]);

            // Create tracking click record if message contains URL placeholder
            $trackingToken = Str::random(16);
            $targetUrl = config('sms.whatsapp_url', 'https://wa.me/');

            Click::create([
                'message_id' => $message->id,
                'token' => $trackingToken,
                'target_url' => $targetUrl,
                'click_count' => 0,
            ]);

            // Update message body with actual tracking URL
            $trackingUrl = route('track.click', ['token' => $trackingToken]);
            $updatedBody = str_replace('{tracking_url}', $trackingUrl, $personalizedBody);
            if ($updatedBody !== $personalizedBody) {
                $message->update(['message_body' => $updatedBody]);
            }

            SendSmsJob::dispatch($message->id)
                ->delay(now()->addSeconds($index * $delaySeconds));

            $index++;
        }

        // Mark as completed after all jobs are dispatched
        // (actual completion happens when all messages are processed via webhooks)
        $campaign->update(['status' => 'sending']);
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
            $optOutUrl = route('optout.form', ['campaign' => $campaignId, 'contact' => $contactId]);
            $optOutLine = str_replace('{opt_out_url}', $optOutUrl, $optOutText);
            $personalized .= "\n" . $optOutLine;
        }

        return $personalized;
    }
}
