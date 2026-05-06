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
            Court::updateOrCreate(
                ['local_id' => $sf->id, 'name' => 'Cancha Fútbol 5 A'],
                [
                    'category' => 'Fútbol 5',
                    'number' => '1',
                    'price_per_hour' => 30.00,
                    'description' => 'Cancha de grama sintética ideal para partidos rápidos.',
                    'status' => 'active'
                ]
            );
            Court::updateOrCreate(
                ['local_id' => $sf->id, 'name' => 'Cancha Fútbol 5 B'],
                [
                    'category' => 'Fútbol 5',
                    'number' => '2',
                    'price_per_hour' => 30.00,
                    'description' => 'Cancha de grama sintética con iluminación nocturna.',
                    'status' => 'active'
                ]
            );
            Court::updateOrCreate(
                ['local_id' => $sf->id, 'name' => 'Cancha Fútbol 7 Principal'],
                [
                    'category' => 'Fútbol 7',
                    'number' => '3',
                    'price_per_hour' => 50.00,
                    'description' => 'Cancha amplia para 7 contra 7, grama de alta calidad.',
                    'status' => 'active'
                ]
            );
        }

        if ($cde) {
            Court::updateOrCreate(
                ['local_id' => $cde->id, 'name' => 'Cancha Fútbol 11 Estadio'],
                [
                    'category' => 'Fútbol 11',
                    'number' => '1',
                    'price_per_hour' => 100.00,
                    'description' => 'Cancha reglamentaria con medidas FIFA.',
                    'status' => 'active'
                ]
            );
            Court::updateOrCreate(
                ['local_id' => $cde->id, 'name' => 'Cancha Fútbol 7 Auxiliar'],
                [
                    'category' => 'Fútbol 7',
                    'number' => '2',
                    'price_per_hour' => 45.00,
                    'description' => 'Excelente drenaje y visibilidad.',
                    'status' => 'active'
                ]
            );
        }
    }
}
