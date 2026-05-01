<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Account;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]
        );

        // Create initial accounts for the admin
        Account::updateOrCreate(
            ['name' => 'Main Bank', 'user_id' => $admin->id],
            ['balance' => 5000.00]
        );

        Account::updateOrCreate(
            ['name' => 'Cash', 'user_id' => $admin->id],
            ['balance' => 500.00]
        );
    }
}
