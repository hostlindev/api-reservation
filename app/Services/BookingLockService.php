<?php

namespace App\Services;

use App\Models\Local;
use App\Models\Court;
use App\Models\BookingLock;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Exception;

class BookingLockService
{
    protected $availabilityService;

    public function __construct(BookingAvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    /**
     * Attempts to create a 10-minute lock for a specific court.
     */
    public function lockCourt(Local $local, int $courtId, Carbon $startTime, Carbon $endTime): ?array
    {
        // 1. Clean up old locks first (optimization)
        BookingLock::where('expires_at', '<=', now())->delete();

        // 2. Validate duration is multiple of local min_booking_duration
        $duration = $startTime->diffInMinutes($endTime);
        if ($duration % $local->min_booking_duration !== 0) {
            throw new Exception("The duration must be a multiple of {$local->min_booking_duration} minutes.");
        }

        try {
            DB::beginTransaction();

            // 3. Find the specific court and lock it for update
            $court = Court::where('id', $courtId)
                ->where('local_id', $local->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (!$court) {
                throw new Exception("Court not found or inactive.");
            }

            // 4. Check if this specific court is free in the requested range
            $isActiveLock = BookingLock::where('court_id', $court->id)
                ->where('expires_at', '>', now())
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->whereBetween('start_time', [$startTime, $endTime->copy()->subMinute()])
                        ->orWhereBetween('end_time', [$startTime->copy()->addMinute(), $endTime])
                        ->orWhere(function ($q) use ($startTime, $endTime) {
                            $q->where('start_time', '<=', $startTime)
                                ->where('end_time', '>=', $endTime);
                        });
                })->exists();

            if ($isActiveLock) {
                throw new Exception("The selected time is currently locked by another user.");
            }

            $hasBooking = $court->bookings()
                ->whereIn('status', ['confirmed', 'pending'])
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->whereBetween('start_time', [$startTime, $endTime->copy()->subMinute()])
                        ->orWhereBetween('end_time', [$startTime->copy()->addMinute(), $endTime])
                        ->orWhere(function ($q) use ($startTime, $endTime) {
                            $q->where('start_time', '<=', $startTime)
                                ->where('end_time', '>=', $endTime);
                        });
                })->exists();

            if ($hasBooking) {
                throw new Exception("The selected time is already booked.");
            }

            // 5. Create the lock
            $sessionId = (string) Str::uuid();
            $lock = BookingLock::create([
                'court_id' => $court->id,
                'session_id' => $sessionId,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'expires_at' => now()->addMinutes(10)
            ]);

            DB::commit();

            return [
                'lock_id' => $lock->id,
                'session_id' => $sessionId,
                'court_number' => $court->number,
                'expires_at' => $lock->expires_at
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
