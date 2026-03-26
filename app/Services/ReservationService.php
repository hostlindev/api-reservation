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

            $booking = Booking::create([
                'court_id' => $lock->court_id,
                'name' => $userData['name'],
                'lastname' => $userData['lastname'],
                'id_card' => $userData['id_card'],
                'email' => $userData['email'],
                'start_time' => $lock->start_time,
                'end_time' => $lock->end_time,
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
