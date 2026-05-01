<?php

namespace Database\Seeders;

use App\Models\User;
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
            ['email' => 'statelyworld@gmail.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('Gj2510#'),
                'is_admin' => true,
            ]
        );
    }
}
