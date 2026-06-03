<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Message;
use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function twilioStatus(Request $request, TwilioService $twilio)
    {
        // Validate Twilio signature
        $signature = $request->header('X-Twilio-Signature', '');
        $url = $request->url();
        $params = $request->all();

        if (!$twilio->validateRequest($signature, $url, $params)) {
            Log::warning('Invalid Twilio webhook signature', ['ip' => $request->ip()]);
            return response('Forbidden', 403);
        }

        $messageSid = $request->input('MessageSid');
        $messageStatus = $request->input('MessageStatus');
        $errorCode = $request->input('ErrorCode');

        if (!$messageSid || !$messageStatus) {
            return response('Bad Request', 400);
        }

        $message = Message::where('twilio_sid', $messageSid)->first();

        if (!$message) {
            Log::info('Twilio webhook: message not found', ['sid' => $messageSid]);
            return response('OK', 200);
        }

        $updateData = ['status' => $messageStatus];

        if ($messageStatus === 'delivered') {
            $updateData['delivered_at'] = now();
        }

        if ($errorCode) {
            $updateData['error_code'] = $errorCode;
            $updateData['error_message'] = $request->input('ErrorMessage', '');
        }

        $message->update($updateData);

        // Check if all messages in campaign are done
        $this->checkCampaignCompletion($message->campaign_id);

        return response('OK', 200);
    }

    private function checkCampaignCompletion(int $campaignId): void
    {
        $campaign = Campaign::find($campaignId);
        if (!$campaign || $campaign->status !== 'sending') {
            return;
        }

        $pendingCount = $campaign->messages()
            ->whereIn('status', ['pending', 'queued', 'sent'])
            ->count();

        if ($pendingCount === 0) {
            $campaign->update(['status' => 'completed']);
        }
    }
}
