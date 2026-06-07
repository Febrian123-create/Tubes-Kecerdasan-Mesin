<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@credisense.id'],
            [
                'name'     => 'Admin CrediSense',
                'password' => Hash::make('admin123!'),
                'role'     => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'borrower@credisense.id'],
            [
                'name'     => 'Demo Peminjam',
                'password' => Hash::make('borrower123!'),
                'role'     => 'borrower',
            ]
        );

        $this->command->info('Seeded: admin@credisense.id / admin123!');
        $this->command->info('Seeded: borrower@credisense.id / borrower123!');
    }
}
