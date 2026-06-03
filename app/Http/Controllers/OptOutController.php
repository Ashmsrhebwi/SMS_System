<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\OptOut;
use Illuminate\Http\Request;

class OptOutController extends Controller
{
    public function form(Request $request)
    {
        $campaign = \App\Models\Campaign::find($request->query('campaign'));
        $contact = Contact::find($request->query('contact'));

        return view('optout.form', compact('campaign', 'contact'));
    }

    public function process(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        $phone = $data['phone'];

        // Mark contact as opted out
        Contact::where('phone', $phone)->update(['opted_in' => false]);

        // Record in opt_outs table (ignore duplicate)
        OptOut::firstOrCreate(['phone' => $phone], ['reason' => $data['reason'] ?? '']);

        return view('optout.confirmed');
    }
}
