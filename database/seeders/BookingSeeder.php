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

            // Scenario FULL DAY: Tomorrow - Court ID 1 is fully booked from 10:00 to 22:00
            for ($hour = 10; $hour < 22; $hour++) {
                $startTime = Carbon::tomorrow()->setTime($hour, 0, 0);
                Booking::updateOrCreate(
                    ['court_id' => $courtsSF->first()->id, 'start_time' => $startTime],
                    [
                        'name' => 'Torneo',
                        'lastname' => 'Mañana',
                        'id_card' => '1111111',
                        'email' => 'torneo@example.com',
                        'end_time' => $startTime->copy()->addMinutes(60),
                        'qr_token' => Str::random(32),
                        'status' => 'confirmed'
                    ]
                );
            }

            // Scenario 1: Today - Padel Techada FULL at 18:00
            $dateFull = Carbon::today()->setTime(18, 0, 0);
            foreach ($courtsSF->where('category', 'Padel Techada') as $court) {
                Booking::updateOrCreate(
                    ['court_id' => $court->id, 'start_time' => $dateFull],
                    [
                        'name' => 'Cliente Full',
                        'lastname' => 'Ocupado',
                        'id_card' => '123' . $court->id,
                        'email' => 'full' . $court->id . '@example.com',
                        'end_time' => $dateFull->copy()->addMinutes(60), // sf min_booking_duration is 60
                        'qr_token' => Str::random(32),
                        'status' => 'confirmed'
                    ]
                );
            }

            // Scenario 2: Today - Some availability (10 AM booked)
            $datePartial = Carbon::today()->setTime(10, 0, 0);
            Booking::updateOrCreate(
                ['court_id' => $courtsSF->first()->id, 'start_time' => $datePartial],
                [
                    'name' => 'Juan',
                    'lastname' => 'Perez',
                    'id_card' => '1234567',
                    'email' => 'juan@example.com',
                    'end_time' => $datePartial->copy()->addMinutes(60),
                    'qr_token' => Str::random(32),
                    'status' => 'confirmed'
                ]
            );
        }

        if ($cde) {
            $courtsCDE = $cde->courts;

            // Scenario 3: Today - Tenis FULL at 14:00 (cde duration is 90)
            // But we will book 15:00 for 90 minutes
            $dateTenisFull = Carbon::today()->setTime(14, 0, 0);
            foreach ($courtsCDE as $court) {
                Booking::updateOrCreate(
                    ['court_id' => $court->id, 'start_time' => $dateTenisFull],
                    [
                        'name' => 'Tenista',
                        'lastname' => 'Pro',
                        'id_card' => '999' . $court->id,
                        'email' => 'pro' . $court->id . '@example.com',
                        'end_time' => $dateTenisFull->copy()->addMinutes(90),
                        'qr_token' => Str::random(32),
                        'status' => 'confirmed'
                    ]
                );
            }

            // Scenario 4: Tomorrow - Partial availability
            $dateTenisPartial = Carbon::tomorrow()->setTime(16, 0, 0);
            Booking::updateOrCreate(
                ['court_id' => $courtsCDE->last()->id, 'start_time' => $dateTenisPartial],
                [
                    'name' => 'Maria',
                    'lastname' => 'Gomez',
                    'id_card' => '8888888',
                    'email' => 'maria@example.com',
                    'end_time' => $dateTenisPartial->copy()->addMinutes(90),
                    'qr_token' => Str::random(32),
                    'status' => 'confirmed'
                ]
            );
        }
    }
}
