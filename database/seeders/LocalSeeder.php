<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Local;

class LocalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $scheduleConfig = [
            "1" => ["is_open" => true, "open_time" => "08:00", "close_time" => "23:00"],
            "2" => ["is_open" => true, "open_time" => "08:00", "close_time" => "23:00"],
            "3" => ["is_open" => true, "open_time" => "08:00", "close_time" => "23:00"],
            "4" => ["is_open" => true, "open_time" => "08:00", "close_time" => "23:00"],
            "5" => ["is_open" => true, "open_time" => "08:00", "close_time" => "23:59"],
            "6" => ["is_open" => true, "open_time" => "10:00", "close_time" => "23:59"],
            "7" => ["is_open" => true, "open_time" => "10:00", "close_time" => "20:00"]
        ];

        Local::updateOrCreate(
            ['slug' => 'padel-club-san-francisco'],
            [
                'name' => 'Padel Club San Francisco',
                'address' => 'San Francisco, Calle 50, Panamá',
                'min_booking_duration' => 60,
                'schedule_config' => json_encode($scheduleConfig),
            ]
        );

        Local::updateOrCreate(
            ['slug' => 'centro-deportivo-costa-del-este'],
            [
                'name' => 'Centro Deportivo Costa del Este',
                'address' => 'Costa del Este, Av Centenario, Panamá',
                'min_booking_duration' => 90,
                'schedule_config' => json_encode($scheduleConfig),
            ]
        );
    }
}
