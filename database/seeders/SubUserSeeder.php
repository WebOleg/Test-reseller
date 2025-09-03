<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SubUser;
use App\Models\Transaction;
use Illuminate\Database\Seeder;

class SubUserSeeder extends Seeder
{
    public function run(): void
    {
        // Get current user or create demo user
        $user = User::first();
        if (!$user) {
            $user = User::factory()->create([
                'name' => 'Demo User',
                'email' => 'demo@example.com',
                'password' => bcrypt('password'),
                'balance' => 1000.00,
            ]);
        }

        // Create 30 sub users for this user
        SubUser::factory(30)
            ->for($user)
            ->create()
            ->each(function ($subUser) use ($user) {
                // Create 3-8 transactions for each sub user
                Transaction::factory(rand(3, 8))
                    ->create([
                        'user_id' => $user->id,
                        'sub_user_id' => $subUser->id,
                    ]);
            });

        // Create some user-level transactions (deposits)
        Transaction::factory(15)
            ->create([
                'user_id' => $user->id,
                'sub_user_id' => null,
                'type' => 'deposit',
            ]);

        $this->command->info('Created 30 sub users with transactions using Factory');
    }
}
