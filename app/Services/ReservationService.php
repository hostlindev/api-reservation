<?php

namespace App\Services;

use App\Models\Local;
use App\Models\Court;
use App\Models\Booking;
use App\Models\BookingLock;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Exception;

class ReservationService
{
    /**
     * Confirms a locked booking and turns it into a real booking.
     */
    public function confirmBooking(Local $local, int $lockId, string $sessionId, array $userData): Booking
    {
        // 1. Find the lock
        $lock = BookingLock::where('id', $lockId)
            ->where('session_id', $sessionId)
            ->first();

        if (!$lock) {
            throw new Exception("Lock not found for this session.");
        }

        // 2. Validate expiration
        if ($lock->expires_at->isPast()) {
            $lock->delete();
            throw new Exception("This reservation lock has expired. Please try again.");
        }

        try {
            DB::beginTransaction();

            // 3. Create the firm booking
            $qrToken = (string) Str::uuid();
            $court = $lock->court;
            $durationInMinutes = $lock->start_time->diffInMinutes($lock->end_time);
            $totalPrice = ($court->price_per_hour / 60) * $durationInMinutes;

            $booking = Booking::create([
                'court_id' => $lock->court_id,
                'name' => $userData['name'],
                'lastname' => $userData['lastname'],
                'id_card' => $userData['id_card'],
                'email' => $userData['email'],
                'start_time' => $lock->start_time,
                'end_time' => $lock->end_time,
                'total_price' => $totalPrice,
                'qr_token' => $qrToken,
                'status' => 'confirmed',
            ]);

            // 4. Release the lock
            $lock->delete();

            DB::commit();

            return $booking;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
