<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    use ApiResponser;

    /**
     * Get financial summary for a specific date.
     */
    public function finances(Request $request)
    {
        $date = $request->date ? Carbon::parse($request->date) : now();

        // TenantScope automatically applies filtering by local_id for local_admin and staff
        $query = Booking::whereDate('start_time', $date);

        // Earnings: Sum of total_price of confirmed bookings
        $earnings = (clone $query)->where('status', 'confirmed')->sum('total_price');

        // Losses: Sum of total_price of cancelled bookings
        $losses = (clone $query)->where('status', 'cancelled')->sum('total_price');

        $totalBookings = $query->count();
        $confirmedCount = (clone $query)->where('status', 'confirmed')->count();
        $cancelledCount = (clone $query)->where('status', 'cancelled')->count();

        return $this->successResponse([
            'date' => $date->toDateString(),
            'earnings' => (float) $earnings,
            'losses' => (float) $losses,
            'total_bookings' => $totalBookings,
            'confirmed_count' => $confirmedCount,
            'cancelled_count' => $cancelledCount,
        ], 'Financial data retrieved successfully.');
    }
}
