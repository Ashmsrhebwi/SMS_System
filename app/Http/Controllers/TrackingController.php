<?php

namespace App\Http\Controllers;

use App\Models\Click;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function click(string $token)
    {
        $click = Click::where('token', $token)->first();

        if (!$click) {
            abort(404);
        }

        $click->increment('click_count');

        if ($click->click_count === 1 || $click->first_clicked_at === null) {
            $click->update(['first_clicked_at' => now()]);
        }

        return redirect($click->target_url);
    }
}
