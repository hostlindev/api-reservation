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
                ['local_id' => $sf->id, 'name' => 'Cancha 1 SF'],
                [
                    'category' => 'Padel Techada',
                    'number' => '1',
                    'price_per_hour' => 20.00,
                    'description' => 'Disfruta de nuestra mejor cancha techada con iluminación LED de alta intensidad.',
                    'status' => 'active'
                ]
            );
            Court::updateOrCreate(
                ['local_id' => $sf->id, 'name' => 'Cancha 2 SF'],
                [
                    'category' => 'Padel Techada',
                    'number' => '2',
                    'price_per_hour' => 20.00,
                    'description' => 'Cancha techada premium con grama sintética de última generación.',
                    'status' => 'active'
                ]
            );
            Court::updateOrCreate(
                ['local_id' => $sf->id, 'name' => 'Cancha 3 SF'],
                [
                    'category' => 'Padel Abierta',
                    'number' => '3',
                    'price_per_hour' => 15.00,
                    'description' => 'Cancha al aire libre bajo el sol, ideal para sesiones de entrenamiento matutinas.',
                    'status' => 'active'
                ]
            );
        }

        if ($cde) {
            Court::updateOrCreate(
                ['local_id' => $cde->id, 'name' => 'Cancha 1 CDE'],
                [
                    'category' => 'Tenis',
                    'number' => '1',
                    'price_per_hour' => 15.00,
                    'description' => 'Cancha de tenis de arcilla profesional.',
                    'status' => 'active'
                ]
            );
            Court::updateOrCreate(
                ['local_id' => $cde->id, 'name' => 'Cancha 2 CDE'],
                [
                    'category' => 'Tenis',
                    'number' => '2',
                    'price_per_hour' => 15.00,
                    'description' => 'Cancha de tenis de superficie rápida en excelentes condiciones.',
                    'status' => 'active'
                ]
            );
        }
    }
}
