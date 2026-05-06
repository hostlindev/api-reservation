<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Local;
use App\Traits\ApiResponser;
use App\Services\BookingAvailabilityService;
use App\Services\BookingLockService;
use App\Services\ReservationService;
use App\Http\Requests\CheckAvailabilityRequest;
use App\Http\Requests\CreateLockRequest;
use App\Http\Requests\ConfirmBookingRequest;
use Carbon\Carbon;
use Exception;

class BookingController extends Controller
{
    use ApiResponser;

    protected $availabilityService;
    protected $lockService;
    protected $reservationService;

    public function __construct(
        BookingAvailabilityService $availabilityService,
        BookingLockService $lockService,
        ReservationService $reservationService
    ) {
        $this->availabilityService = $availabilityService;
        $this->lockService = $lockService;
        $this->reservationService = $reservationService;
    }

    /**
     * Display availability for a specific local and category.
     */
    public function getAvailability(Local $local, CheckAvailabilityRequest $request)
    {
        $date = Carbon::parse($request->date);

        $availableSlots = $this->availabilityService->getAvailableCourts(
            $local,
            $request->category,
            $date
        );

        return $this->successResponse($availableSlots, 'Available slots retrieved successfully.');
    }

    /**
     * Attempt to create a 10-minute lock for a court.
     */
    public function createLock(Local $local, CreateLockRequest $request)
    {
        $startTime = Carbon::parse($request->start_time);
        $duration = $request->duration ?? $local->min_booking_duration;
        $endTime = $startTime->copy()->addMinutes($duration);

        try {
            $lockData = $this->lockService->lockCourt(
                $local,
                $request->court_id,
                $startTime,
                $endTime
            );

            return $this->successResponse($lockData, 'Court locked successfully for 10 minutes.', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 409); // 409 Conflict
        }
    }

    /**
     * Confirm booking and finalize reservation.
     */
    public function confirm(Local $local, ConfirmBookingRequest $request)
    {
        try {
            $booking = $this->reservationService->confirmBooking(
                $local,
                $request->lock_id,
                $request->session_id,
                $request->validated()
            );

            return $this->successResponse($booking, 'Booking confirmed successfully.', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400); // 400 Bad Request
        }
    }
}
