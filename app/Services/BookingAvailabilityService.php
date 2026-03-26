<?php

namespace App\Services;

use App\Models\Local;
use App\Models\Court;
use App\Models\Booking;
use App\Models\BookingLock;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BookingAvailabilityService
{
    /**
     * Check if a given category has available courts for a specific time block.
     */
    public function getAvailableCourts(Local $local, string $category, Carbon $date): Collection
    {
        // 1. Check if the local is open on this date based on schedule_config
        // This is a simplified check, in reality you'd parse schedule_config
        $openingTime = $this->getOpeningTime($local, $date);
        $closingTime = $this->getClosingTime($local, $date);

        if (!$openingTime || !$closingTime) {
            return collect([]); // Local is closed
        }

        // 2. Generate time slots (e.g., every 30 mins)
        $slots = $this->generateTimeSlots($openingTime, $closingTime, $local->min_booking_duration);
        $availableSlots = collect([]);

        // 3. Find all courts of this category
        $courtsCount = Court::where('local_id', $local->id)
            ->where('category', $category)
            ->where('status', 'active')
            ->count();

        if ($courtsCount === 0) {
            return collect([]);
        }

        // 4. For each slot, check availability
        foreach ($slots as $slot) {
            $slotStart = $slot['start_time'];
            $slotEnd = $slot['end_time'];

            // Count active locks for this category and time
            $locksCount = BookingLock::whereHas('court', function ($q) use ($local, $category) {
                $q->where('local_id', $local->id)->where('category', $category);
            })
                ->where(function ($query) use ($slotStart, $slotEnd) {
                    $query->whereBetween('start_time', [$slotStart, $slotEnd->copy()->subMinute()])
                        ->orWhereBetween('end_time', [$slotStart->copy()->addMinute(), $slotEnd])
                        ->orWhere(function ($q) use ($slotStart, $slotEnd) {
                            $q->where('start_time', '<=', $slotStart)
                                ->where('end_time', '>=', $slotEnd);
                        });
                })
                ->where('expires_at', '>', now()) // Only active locks
                ->count();

            // Count confirmed bookings for this category and time
            $bookingsCount = Booking::whereHas('court', function ($q) use ($local, $category) {
                $q->where('local_id', $local->id)->where('category', $category);
            })
                ->whereIn('status', ['confirmed'])
                ->where(function ($query) use ($slotStart, $slotEnd) {
                    $query->whereBetween('start_time', [$slotStart, $slotEnd->copy()->subMinute()])
                        ->orWhereBetween('end_time', [$slotStart->copy()->addMinute(), $slotEnd])
                        ->orWhere(function ($q) use ($slotStart, $slotEnd) {
                            $q->where('start_time', '<=', $slotStart)
                                ->where('end_time', '>=', $slotEnd);
                        });
                })
                ->count();

            // Total occupied
            $totalOccupied = $locksCount + $bookingsCount;

            if ($totalOccupied < $courtsCount) {
                // There is at least 1 court available
                $slot['available_courts_count'] = $courtsCount - $totalOccupied;
                $availableSlots->push($slot);
            }
        }

        return $availableSlots;
    }

    /**
     * Check if a specific time is available (used before locking)
     */
    public function isTimeAvailable(Local $local, string $category, Carbon $startTime, Carbon $endTime): bool
    {
        // Must respect closing time
        $closingTime = $this->getClosingTime($local, $startTime);
        if (!$closingTime || $endTime->gt($closingTime)) {
            return false; // Full reservada (exceeds closing time)
        }

        $courtsCount = Court::where('local_id', $local->id)
            ->where('category', $category)
            ->where('status', 'active')
            ->count();

        if ($courtsCount === 0) return false;

        $locksCount = BookingLock::whereHas('court', function ($q) use ($local, $category) {
            $q->where('local_id', $local->id)->where('category', $category);
        })
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime->copy()->subMinute()])
                    ->orWhereBetween('end_time', [$startTime->copy()->addMinute(), $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            })
            ->where('expires_at', '>', now())
            ->count();

        $bookingsCount = Booking::whereHas('court', function ($q) use ($local, $category) {
            $q->where('local_id', $local->id)->where('category', $category);
        })
            ->whereIn('status', ['confirmed', 'pending']) // Both take up space
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime->copy()->subMinute()])
                    ->orWhereBetween('end_time', [$startTime->copy()->addMinute(), $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            })
            ->count();

        return ($locksCount + $bookingsCount) < $courtsCount;
    }

    private function getOpeningTime(Local $local, Carbon $date): ?Carbon
    {
        // TODO: Read from $local->schedule_config based on $date raw day of week
        // Default to 10 AM for now
        $time = $date->copy()->setTime(10, 0, 0);
        return $time;
    }

    private function getClosingTime(Local $local, Carbon $date): ?Carbon
    {
        // Default to 11 PM
        $time = $date->copy()->setTime(23, 0, 0);
        return $time;
    }

    private function generateTimeSlots(Carbon $start, Carbon $end, int $durationMinutes): array
    {
        $slots = [];
        $current = $start->copy();

        // Example: slots every 30 minutes, but duration is min_booking_duration
        while ($current->copy()->addMinutes($durationMinutes)->lte($end)) {
            $slots[] = [
                'start_time' => $current->copy(),
                'end_time' => $current->copy()->addMinutes($durationMinutes),
            ];
            $current->addMinutes(30); // Step every 30 minutes
        }

        return $slots;
    }
}
