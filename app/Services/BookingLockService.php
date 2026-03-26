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
     * Attempts to create a 10-minute lock for a category.
     */
    public function lockCourt(Local $local, string $category, Carbon $startTime, Carbon $endTime): ?array
    {
        // 1. Clean up old locks first (optimization)
        BookingLock::where('expires_at', '<=', now())->delete();

        // 2. Validate availability overall before locking DB
        if (!$this->availabilityService->isTimeAvailable($local, $category, $startTime, $endTime)) {
            throw new Exception("The selected time is full for category: {$category}");
        }

        try {
            DB::beginTransaction();

            // 3. Find an available physical court utilizing Pessimistic Locking
            $courts = Court::where('local_id', $local->id)
                ->where('category', $category)
                ->where('status', 'active')
                ->lockForUpdate() // Lock these rows to prevent conditions
                ->get();

            $availableCourt = null;

            foreach ($courts as $court) {
                // Check if this specific court is free
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

                if ($isActiveLock) continue;

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

                if ($hasBooking) continue;

                // Found an available court!
                $availableCourt = $court;
                break;
            }

            if (!$availableCourt) {
                DB::rollBack();
                throw new Exception("No available courts could be locked at this time due to high concurrency.");
            }

            // 4. Create the lock
            $sessionId = (string) Str::uuid();
            $lock = BookingLock::create([
                'court_id' => $availableCourt->id,
                'session_id' => $sessionId,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'expires_at' => now()->addMinutes(10)
            ]);

            DB::commit();

            return [
                'lock_id' => $lock->id,
                'session_id' => $sessionId,
                'court_number' => $availableCourt->number,
                'expires_at' => $lock->expires_at
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
