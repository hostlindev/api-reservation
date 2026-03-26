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
        Local::create([
            'name' => 'Padel Club San Francisco',
            'slug' => 'padel-club-san-francisco',
            'address' => 'San Francisco, Calle 50, Panamá',
            'min_booking_duration' => 60,
        ]);

        Local::create([
            'name' => 'Centro Deportivo Costa del Este',
            'slug' => 'centro-deportivo-costa-del-este',
            'address' => 'Costa del Este, Av Centenario, Panamá',
            'min_booking_duration' => 90,
        ]);
    }
}
