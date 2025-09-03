<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SubUser;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo user
        $user = User::firstOrCreate(
            ['email' => 'demo@dataimpulse-reseller.com'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('demo123'),
                'balance' => 500.00,
            ]
        );

        // Create sub users using factory
        SubUser::factory(5)
            ->for($user)
            ->sequence(
                ['username' => 'marketing_team', 'email' => 'marketing@company.com', 'balance' => 250.50],
                ['username' => 'seo_tools', 'email' => 'seo@company.com', 'balance' => 180.75],
                ['username' => 'data_scraping', 'email' => 'data@company.com', 'balance' => 95.25],
                ['username' => 'social_media', 'email' => 'social@company.com', 'balance' => 45.00],
                ['username' => 'research_team', 'email' => 'research@company.com', 'balance' => 15.80]
            )
            ->create()
            ->each(function ($subUser) use ($user) {
                // Create transactions for each sub user
                Transaction::factory(rand(5, 12))->create([
                    'user_id' => $user->id,
                    'sub_user_id' => $subUser->id,
                ]);
            });

        // Create user-level transactions
        Transaction::factory(10)->create([
            'user_id' => $user->id,
            'sub_user_id' => null,
            'type' => 'deposit',
        ]);
    }
}
