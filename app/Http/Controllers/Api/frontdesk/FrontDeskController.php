<?php

namespace App\Http\Controllers\Api\frontdesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FrontDeskController extends Controller
{
    public function stats(Request $request)
    {
        // Simple stats, perhaps count reservations, rooms, etc.
        // For now, return empty or basic stats
        return response()->json([
            'total_reservations' => 0,
            'total_rooms' => 0,
            'occupied_rooms' => 0,
            'available_rooms' => 0,
        ]);
    }
}
