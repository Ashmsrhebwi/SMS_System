<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Message;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_contacts' => Contact::count(),
            'opted_in' => Contact::where('opted_in', true)->count(),
            'total_campaigns' => Campaign::count(),
            'active_campaigns' => Campaign::whereIn('status', ['sending', 'scheduled'])->count(),
            'total_messages_sent' => Message::whereIn('status', ['sent', 'delivered', 'failed', 'undelivered'])->count(),
            'total_delivered' => Message::where('status', 'delivered')->count(),
        ];

        $recentCampaigns = Campaign::latest()->take(5)->get();

        return view('dashboard', compact('stats', 'recentCampaigns'));
    }
}
