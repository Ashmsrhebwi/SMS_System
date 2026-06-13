<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CompleteStuckCampaigns extends Command
{
    protected $signature   = 'campaigns:complete-stuck {--hours=6 : Mark campaigns stuck longer than this many hours}';
    protected $description = 'Complete campaigns stuck in sending when carriers never send delivery receipts';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        // Find campaigns stuck in "sending" for longer than $hours
        $stuck = Campaign::where('status', 'sending')
            ->where('updated_at', '<', $cutoff)
            ->get();

        if ($stuck->isEmpty()) {
            $this->info('No stuck campaigns found.');
            return 0;
        }

        foreach ($stuck as $campaign) {
            // Count messages still waiting for a webhook (pending/queued/sent)
            $waiting = $campaign->messages()
                ->whereIn('status', ['pending', 'queued'])
                ->count();

            $stuckSent = $campaign->messages()
                ->where('status', 'sent')
                ->where('updated_at', '<', $cutoff)
                ->count();

            if ($waiting > 0) {
                // Still genuinely pending — leave it alone
                $this->line("Campaign #{$campaign->id} ({$campaign->name}): {$waiting} still pending, skipping.");
                continue;
            }

            // All messages are either terminal or stuck at "sent" with no webhook
            // Mark old "sent" messages as "undelivered" (carrier gave no receipt)
            if ($stuckSent > 0) {
                Message::where('campaign_id', $campaign->id)
                    ->where('status', 'sent')
                    ->where('updated_at', '<', $cutoff)
                    ->update([
                        'status'        => 'undelivered',
                        'error_message' => 'No delivery receipt received from carrier',
                    ]);

                $this->line("Campaign #{$campaign->id}: marked {$stuckSent} sent→undelivered.");
            }

            $campaign->update(['status' => 'completed']);

            Log::info("CompleteStuckCampaigns: completed campaign #{$campaign->id}", [
                'stuck_sent' => $stuckSent,
                'hours'      => $hours,
            ]);

            $this->info("Campaign #{$campaign->id} ({$campaign->name}): marked completed.");
        }

        return 0;
    }
}
