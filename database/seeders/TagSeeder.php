<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        if ($users->isEmpty()) return;

        $tags = [
            ['name' => 'Rent', 'color' => '#ef4444'],
            ['name' => 'Salary', 'color' => '#22c55e'],
            ['name' => 'Shopping', 'color' => '#f59e0b'],
            ['name' => 'Food', 'color' => '#f97316'],
            ['name' => 'EMI', 'color' => '#8b5cf6'],
            ['name' => 'Credit Card Payment', 'color' => '#6366f1'],
            ['name' => 'Entertainment', 'color' => '#ec4899'],
            ['name' => 'Travel', 'color' => '#14b8a6'],
            ['name' => 'Bills', 'color' => '#0ea5e9'],
            ['name' => 'Grocery', 'color' => '#84cc16'],
            ['name' => 'Daily Essentials', 'color' => '#a855f7'],
            ['name' => 'Electric Bill', 'color' => '#eab308'],
            ['name' => 'Furniture', 'color' => '#78716c'],
            ['name' => 'Gas', 'color' => '#64748b'],
            ['name' => 'Househelp', 'color' => '#d946ef'],
            ['name' => 'Medical', 'color' => '#dc2626'],
            ['name' => 'Milk', 'color' => '#fbbf24'],
            ['name' => 'Opening Balance', 'color' => '#3b82f6'],
            ['name' => 'Other', 'color' => '#94a3b8'],
            ['name' => 'Party', 'color' => '#e11d48'],
            ['name' => 'Saving', 'color' => '#059669'],
            ['name' => 'Veg & Fruits', 'color' => '#15803d'],
        ];

        $admin = User::where('is_admin', true)->first() ?: User::first();
        if (!$admin) return;

        foreach ($tags as $tag) {
            Tag::firstOrCreate(
                ['name' => $tag['name']],
                [
                    'color' => $tag['color'],
                    'user_id' => $admin->id
                ]
            );
        }
    }
}
