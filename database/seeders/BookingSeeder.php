<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Local;
use App\Models\Court;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sf = Local::where('slug', 'padel-club-san-francisco')->first();
        $cde = Local::where('slug', 'centro-deportivo-costa-del-este')->first();

        if ($sf) {
            $courtsSF = $sf->courts;
            if ($courtsSF->count() > 0) {
                $court = $courtsSF->first();

                // Scenario FULL DAY: Tomorrow - Court is fully booked from 10:00 to 22:00
                for ($hour = 10; $hour < 22; $hour++) {
                    $startTime = Carbon::tomorrow()->setTime($hour, 0, 0);
                    $duration = 60;
                    $totalPrice = ($court->price_per_hour / 60) * $duration;

                    Booking::updateOrCreate(
                        ['court_id' => $court->id, 'start_time' => $startTime],
                        [
                            'name' => 'Torneo Fútbol 5',
                            'lastname' => 'Mañana',
                            'id_card' => '1111111',
                            'email' => 'torneo@example.com',
                            'end_time' => $startTime->copy()->addMinutes($duration),
                            'total_price' => $totalPrice,
                            'qr_token' => Str::random(32),
                            'status' => 'confirmed'
                        ]
                    );
                }

                // Scenario 1: Today - Football 5 courts busy at 18:00
                $dateFull = Carbon::today()->setTime(18, 0, 0);
                foreach ($courtsSF->where('category', 'Fútbol 5') as $courtF5) {
                    $duration = 60;
                    $totalPrice = ($courtF5->price_per_hour / 60) * $duration;

                    Booking::updateOrCreate(
                        ['court_id' => $courtF5->id, 'start_time' => $dateFull],
                        [
                            'name' => 'Partido Amigos',
                            'lastname' => 'Ocupado',
                            'id_card' => '123' . $courtF5->id,
                            'email' => 'fútbol' . $courtF5->id . '@example.com',
                            'end_time' => $dateFull->copy()->addMinutes($duration),
                            'total_price' => $totalPrice,
                            'qr_token' => Str::random(32),
                            'status' => 'confirmed'
                        ]
                    );
                }

                // Scenario 2: Today - Cancelled booking for finance testing
                $dateCancelled = Carbon::today()->setTime(9, 0, 0);
                $duration = 60;
                $totalPrice = ($court->price_per_hour / 60) * $duration;

                Booking::updateOrCreate(
                    ['court_id' => $court->id, 'start_time' => $dateCancelled],
                    [
                        'name' => 'Pedro',
                        'lastname' => 'Fútbol',
                        'id_card' => '7777777',
                        'email' => 'pedro@example.com',
                        'end_time' => $dateCancelled->copy()->addMinutes($duration),
                        'total_price' => $totalPrice,
                        'qr_token' => Str::random(32),
                        'status' => 'cancelled'
                    ]
                );
            }
        }

        if ($cde) {
            $courtsCDE = $cde->courts;
            if ($courtsCDE->count() > 0) {

                // Scenario 3: Today - Football 7 busy at 14:00
                $dateFull = Carbon::today()->setTime(14, 0, 0);
                foreach ($courtsCDE->where('category', 'Fútbol 7') as $courtF7) {
                    $duration = 90;
                    $totalPrice = ($courtF7->price_per_hour / 60) * $duration;

                    Booking::updateOrCreate(
                        ['court_id' => $courtF7->id, 'start_time' => $dateFull],
                        [
                            'name' => 'Entrenamiento',
                            'lastname' => 'Club',
                            'id_card' => '999' . $courtF7->id,
                            'email' => 'club' . $courtF7->id . '@example.com',
                            'end_time' => $dateFull->copy()->addMinutes($duration),
                            'total_price' => $totalPrice,
                            'qr_token' => Str::random(32),
                            'status' => 'confirmed'
                        ]
                    );
                }
                
                // Scenario 4: Multiple hours booking for Football 11
                $courtF11 = $courtsCDE->where('category', 'Fútbol 11')->first();
                if ($courtF11) {
                    $dateMulti = Carbon::tomorrow()->setTime(14, 0, 0);
                    $duration = 180; // 3 hours
                    $totalPrice = ($courtF11->price_per_hour / 60) * $duration;

                    Booking::updateOrCreate(
                        ['court_id' => $courtF11->id, 'start_time' => $dateMulti],
                        [
                            'name' => 'Liga Intercolegial',
                            'lastname' => 'Final',
                            'id_card' => '5555555',
                            'email' => 'liga@example.com',
                            'end_time' => $dateMulti->copy()->addMinutes($duration),
                            'total_price' => $totalPrice,
                            'qr_token' => Str::random(32),
                            'status' => 'confirmed'
                        ]
                    );
                }
            }
        }
    }
}
