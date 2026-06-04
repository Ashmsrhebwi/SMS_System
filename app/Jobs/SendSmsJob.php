<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\ActivityLogger;
use App\Services\SmsSegmentCalculator;
use App\Services\TwilioService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(private int $messageId) {}

    public function handle(TwilioService $twilio): void
    {
        $message = Message::with(['contact', 'campaign'])->findOrFail($this->messageId);

        if ($message->status !== 'pending') {
            return;
        }

        try {
            $statusCallbackUrl = route('webhooks.twilio.status');
            $sid = $twilio->sendMessage(
                $message->contact->phone,
                $message->message_body,
                $statusCallbackUrl
            );

            $segments = SmsSegmentCalculator::segments($message->message_body);
            $cost     = SmsSegmentCalculator::cost($message->message_body);

            $message->update([
                'twilio_sid'   => $sid,
                'status'       => 'queued',
                'sent_at'      => now(),
                'sms_segments' => $segments,
                'cost'         => $cost,
            ]);

            ActivityLogger::smsSent($message);

        } catch (\Exception $e) {
            Log::error('SMS send failed', [
                'message_id' => $this->messageId,
                'error' => $e->getMessage(),
            ]);

            $message->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
