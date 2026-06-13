<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\CampaignCompletionService;
use App\Services\TwilioService;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function twilioStatus(
        Request $request,
        TwilioService $twilio,
        CampaignCompletionService $completion,
    ) {
        $signature = $request->header('X-Twilio-Signature', '');
        $params    = $request->post();

        // Try the canonical route URL first; fall back to the actual request URL
        // to handle shared-hosting proxies where scheme may differ.
        $routeUrl   = route('webhooks.twilio.status');
        $requestUrl = $request->url();

        $valid = $twilio->validateRequest($signature, $routeUrl, $params);

        if (!$valid && $requestUrl !== $routeUrl) {
            $valid = $twilio->validateRequest($signature, $requestUrl, $params);
            if ($valid) {
                Log::info('Twilio webhook: validated via request URL (proxy detected)', [
                    'route_url'   => $routeUrl,
                    'request_url' => $requestUrl,
                ]);
            }
        }

        if (!$valid) {
            Log::warning('Invalid Twilio webhook signature', [
                'ip'          => $request->ip(),
                'route_url'   => $routeUrl,
                'request_url' => $requestUrl,
            ]);
            return response('Forbidden', 403);
        }

        $messageSid    = $request->input('MessageSid');
        $messageStatus = $request->input('MessageStatus');
        $errorCode     = $request->input('ErrorCode');

        Log::info('Twilio webhook received', ['sid' => $messageSid, 'status' => $messageStatus]);

        if (!$messageSid || !$messageStatus) {
            return response('Bad Request', 400);
        }

        $message = Message::where('twilio_sid', $messageSid)->first();

        if (!$message) {
            Log::warning('Twilio webhook: message not found', ['sid' => $messageSid]);
            return response('OK', 200);
        }

        // Never downgrade out of a terminal state
        $terminal = ['delivered', 'undelivered', 'failed'];
        if (in_array($message->status, $terminal, true)) {
            return response('OK', 200);
        }

        // Reject out-of-order non-terminal callbacks
        $priority = ['pending' => 0, 'queued' => 1, 'sending' => 2, 'sent' => 3];
        $currentPriority = $priority[$message->status] ?? 0;
        $newPriority     = $priority[$messageStatus] ?? 0;

        if (array_key_exists($messageStatus, $priority) && $newPriority < $currentPriority) {
            Log::info('Twilio webhook: ignoring out-of-order status', [
                'sid'      => $messageSid,
                'current'  => $message->status,
                'incoming' => $messageStatus,
            ]);
            return response('OK', 200);
        }

        $updateData = ['status' => $messageStatus];

        if ($messageStatus === 'delivered') {
            $updateData['delivered_at'] = now();
        }

        if ($errorCode) {
            $updateData['error_code']    = $errorCode;
            $updateData['error_message'] = $request->input('ErrorMessage', '');
        }

        $message->update($updateData);

        $fresh = $message->fresh(['campaign']);
        if ($messageStatus === 'delivered') {
            ActivityLogger::smsDelivered($fresh);
        } elseif (in_array($messageStatus, ['failed', 'undelivered'], true)) {
            ActivityLogger::smsFailed($fresh);
        }

        $completion->checkAndComplete($message->campaign_id);

        return response('OK', 200);
    }
}
