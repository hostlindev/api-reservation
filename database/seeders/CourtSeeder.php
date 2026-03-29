<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Local;
use App\Models\Court;

class CourtSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sf = Local::where('slug', 'padel-club-san-francisco')->first();
        $cde = Local::where('slug', 'centro-deportivo-costa-del-este')->first();

        if ($sf) {
            Court::create([
                'local_id' => $sf->id,
                'category' => 'Padel Techada',
                'name' => 'Cancha 1 SF',
                'number' => '1',
                'price_per_hour' => 20.00,
                'description' => 'Disfruta de nuestra mejor cancha techada con iluminación LED de alta intensidad.',
                'status' => 'active'
            ]);
            Court::create([
                'local_id' => $sf->id,
                'category' => 'Padel Techada',
                'name' => 'Cancha 2 SF',
                'number' => '2',
                'price_per_hour' => 20.00,
                'description' => 'Cancha techada premium con grama sintética de última generación.',
                'status' => 'active'
            ]);
            Court::create([
                'local_id' => $sf->id,
                'category' => 'Padel Abierta',
                'name' => 'Cancha 3 SF',
                'number' => '3',
                'price_per_hour' => 15.00,
                'description' => 'Cancha al aire libre bajo el sol, ideal para sesiones de entrenamiento matutinas.',
                'status' => 'active'
            ]);
        }

        if ($cde) {
            Court::create([
                'local_id' => $cde->id,
                'category' => 'Tenis',
                'name' => 'Cancha 1 CDE',
                'number' => '1',
                'price_per_hour' => 15.00,
                'description' => 'Cancha de tenis de arcilla profesional.',
                'status' => 'active'
            ]);
            Court::create([
                'local_id' => $cde->id,
                'category' => 'Tenis',
                'name' => 'Cancha 2 CDE',
                'number' => '2',
                'price_per_hour' => 15.00,
                'description' => 'Cancha de tenis de superficie rápida en excelentes condiciones.',
                'status' => 'active'
            ]);
        }
    }
}
