<?php

namespace App\Http\Controllers;

use App\Models\FacebookEventsLog;
use App\Models\Institut;
use Illuminate\Http\Request;

class FacebookEventController extends Controller
{
    /**
     * Reçoit un événement Facebook depuis le navigateur et le log.
     * POST /fb-event/{slug}
     */
    public function log(Request $request, string $slug)
    {
        $institut = Institut::where('slug', $slug)->first();

        if (!$institut || !$institut->facebook_pixel_id) {
            return response()->json(['ok' => false], 404);
        }

        $eventName = $request->input('event', 'PageView');
        $customData = $request->input('data', []);

        FacebookEventsLog::create([
            'institut_id' => $institut->id,
            'event_name'  => $eventName,
            'source'      => 'browser',
            'payload'     => $customData,
            'success'     => true,
        ]);

        return response()->json(['ok' => true]);
    }
}
