<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of the bookings for the authenticated admin's local.
     */
    public function index(Request $request)
    {
        // TenantScope automatically filters this by local_id
        $query = Booking::with('court');

        // Optional filtering by date
        if ($request->has('date')) {
            $query->whereDate('start_time', $request->date);
        }

        $bookings = $query->orderBy('start_time', 'asc')->get();

        return $this->successResponse($bookings, 'Bookings retrieved successfully.');
    }

    /**
     * Display the specified booking.
     */
    public function show(Booking $booking)
    {
        // TenantScope ensures they can only see their own local's bookings
        $booking->load('court');
        return $this->successResponse($booking, 'Booking retrieved successfully.');
    }

    /**
     * Update the specified booking status.
     */
    public function updateStatus(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'status' => 'required|in:confirmed,cancelled'
        ]);

        $booking->update(['status' => $validated['status']]);

        return $this->successResponse($booking, "Booking status updated to {$validated['status']} successfully.");
    }
}
