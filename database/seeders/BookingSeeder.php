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

            // Scenario FULL DAY: March 28th - Court ID 1 is fully booked from 10:00 to 23:00
            for ($hour = 10; $hour < 23; $hour++) {
                $startTime = Carbon::create(2026, 3, 28, $hour, 0, 0);
                Booking::create([
                    'court_id' => 1,
                    'name' => 'Torneo',
                    'lastname' => 'Marzo',
                    'id_card' => '1111111',
                    'email' => 'torneo@example.com',
                    'start_time' => $startTime,
                    'end_time' => $startTime->copy()->addMinutes(60),
                    'qr_token' => Str::random(32),
                    'status' => 'confirmed'
                ]);
            }

            // Scenario 1: March 20th - Padel Techada FULL at 18:00
            $dateFull = Carbon::create(2026, 3, 20, 18, 0, 0);
            foreach ($courtsSF->where('category', 'Padel Techada') as $court) {
                Booking::create([
                    'court_id' => $court->id,
                    'name' => 'Cliente Full',
                    'lastname' => 'Ocupado',
                    'id_card' => '123' . $court->id,
                    'email' => 'full' . $court->id . '@example.com',
                    'start_time' => $dateFull,
                    'end_time' => $dateFull->copy()->addMinutes(90),
                    'qr_token' => Str::random(32),
                    'status' => 'confirmed'
                ]);
            }

            // Scenario 2: April 5th - Some availability
            $datePartial = Carbon::create(2026, 4, 5, 10, 0, 0);
            Booking::create([
                'court_id' => $courtsSF->first()->id,
                'name' => 'Juan',
                'lastname' => 'Perez',
                'id_card' => '1234567',
                'email' => 'juan@example.com',
                'start_time' => $datePartial,
                'end_time' => $datePartial->copy()->addMinutes(60),
                'qr_token' => Str::random(32),
                'status' => 'confirmed'
            ]);
        }

        if ($cde) {
            $courtsCDE = $cde->courts;

            // Scenario 3: March 15th - Tenis FULL at 14:00
            $dateTenisFull = Carbon::create(2026, 3, 15, 14, 0, 0);
            foreach ($courtsCDE as $court) {
                Booking::create([
                    'court_id' => $court->id,
                    'name' => 'Tenista',
                    'lastname' => 'Pro',
                    'id_card' => '999' . $court->id,
                    'email' => 'pro' . $court->id . '@example.com',
                    'start_time' => $dateTenisFull,
                    'end_time' => $dateTenisFull->copy()->addMinutes(60),
                    'qr_token' => Str::random(32),
                    'status' => 'confirmed'
                ]);
            }

            // Scenario 4: April 10th - Partial availability
            $dateTenisPartial = Carbon::create(2026, 4, 10, 16, 0, 0);
            Booking::create([
                'court_id' => $courtsCDE->last()->id,
                'name' => 'Maria',
                'lastname' => 'Gomez',
                'id_card' => '8888888',
                'email' => 'maria@example.com',
                'start_time' => $dateTenisPartial,
                'end_time' => $dateTenisPartial->copy()->addMinutes(120),
                'qr_token' => Str::random(32),
                'status' => 'confirmed'
            ]);
        }
    }
}
