<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Local;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Administrador general (super_admin)
        User::updateOrCreate(
            ['email' => 'superadmin@reservation.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password123'),
                'role' => 'super_admin',
                'local_id' => null, // El super_admin no está atado a un local
            ]
        );

        $sf = Local::where('slug', 'padel-club-san-francisco')->first();
        $cde = Local::where('slug', 'centro-deportivo-costa-del-este')->first();

        // Local Admin 1 (San Francisco)
        if ($sf) {
            User::updateOrCreate(
                ['email' => 'adminsf@reservation.com'],
                [
                    'name' => 'Admin San Francisco',
                    'password' => bcrypt('password123'),
                    'role' => 'local_admin',
                    'local_id' => $sf->id,
                ]
            );
        }

        // Local Admin 2 (Costa del Este)
        if ($cde) {
            User::updateOrCreate(
                ['email' => 'admincde@reservation.com'],
                [
                    'name' => 'Admin Costa del Este',
                    'password' => bcrypt('password123'),
                    'role' => 'local_admin',
                    'local_id' => $cde->id,
                ]
            );
        }
    }
}
