<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\SubUser;  
use App\Models\Transaction;
use App\Services\ResellerApi\ResellerApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateDemoDataCommand extends Command
{
    protected $signature = 'demo:create {--user=demo@dataimpulse-reseller.com}';
    protected $description = 'Create demo data for employer testing';

    private ResellerApiClient $apiClient;

    public function __construct(ResellerApiClient $apiClient)
    {
        parent::__construct();
        $this->apiClient = $apiClient;
    }

    public function handle(): int
    {
        $this->info('Creating demo data for DataImpulse Reseller...');

        // Get or create demo user
        $user = $this->getOrCreateDemoUser();
        
        // Clean existing data
        $this->cleanExistingData($user);
        
        // Create sub users with API integration
        $subUsers = $this->createSubUsers($user);
        
        // Create realistic transactions
        $this->createTransactions($user, $subUsers);
        
        // Update user balance
        $this->updateUserBalance($user);
        
        $this->displaySummary($user);
        
        return Command::SUCCESS;
    }

    private function getOrCreateDemoUser(): User
    {
        $email = $this->option('user');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $user = User::create([
                'name' => 'Demo User',
                'email' => $email,
                'password' => Hash::make('demo123'),
                'balance' => 500.00,
            ]);
            $this->info("Created demo user: {$user->email}");
        }
        
        return $user;
    }

    private function cleanExistingData(User $user): void
    {
        Transaction::where('user_id', $user->id)->delete();
        SubUser::where('user_id', $user->id)->delete();
        $this->info('Cleaned existing demo data');
    }

    private function createSubUsers(User $user): \Illuminate\Support\Collection
    {
        $this->info('Creating sub users...');
        
        $demoUsers = [
            ['username' => 'marketing_team', 'email' => 'marketing@company.com', 'balance' => 250.50, 'status' => 'active'],
            ['username' => 'seo_tools', 'email' => 'seo@company.com', 'balance' => 180.75, 'status' => 'active'],
            ['username' => 'data_scraping', 'email' => 'data@company.com', 'balance' => 95.25, 'status' => 'active'],
            ['username' => 'social_media', 'email' => 'social@company.com', 'balance' => 45.00, 'status' => 'inactive'],
            ['username' => 'research_team', 'email' => 'research@company.com', 'balance' => 15.80, 'status' => 'active']
        ];

        $subUsers = collect();
        
        foreach ($demoUsers as $userData) {
            $subUser = SubUser::create([
                'user_id' => $user->id,
                'username' => $userData['username'],
                'email' => $userData['email'],
                'password' => Hash::make('password123'),
                'balance' => $userData['balance'],
                'status' => $userData['status']
            ]);

            // Try API integration
            try {
                $apiResponse = $this->apiClient->createSubUser([
                    'username' => $userData['username'],
                    'threads' => rand(20, 100)
                ]);
                
                $subUser->update(['api_user_id' => $apiResponse['id']]);
                $this->info("✓ {$userData['username']} - Synced with API (ID: {$apiResponse['id']})");
                
            } catch (\Exception $e) {
                $this->warn("✓ {$userData['username']} - Local only (API: {$e->getMessage()})");
            }
            
            $subUsers->push($subUser);
        }
        
        return $subUsers;
    }

    private function createTransactions(User $user, $subUsers): void
    {
        $this->info('Creating transactions...');
        
        $bar = $this->output->createProgressBar(50);
        
        // Create varied transactions over last 90 days
        for ($i = 0; $i < 50; $i++) {
            $transaction = Transaction::factory()->create([
                'user_id' => $user->id,
                'sub_user_id' => $subUsers->random()->id,
                'created_at' => now()->subDays(rand(1, 90))
            ]);
            
            $bar->advance();
        }
        
        // Create user-level deposits
        Transaction::factory(8)->create([
            'user_id' => $user->id,
            'sub_user_id' => null,
            'type' => 'deposit',
            'created_at' => now()->subDays(rand(1, 60))
        ]);
        
        $bar->finish();
        $this->newLine();
    }

    private function updateUserBalance(User $user): void
    {
        $totalDeposits = $user->transactions()->where('type', 'deposit')->sum('amount');
        $totalSpent = $user->transactions()->where('type', 'charge')->sum('amount');
        $balance = max(0, $totalDeposits - $totalSpent);
        
        $user->update(['balance' => $balance]);
    }

    private function displaySummary(User $user): void
    {
        $this->newLine();
        $this->info('Demo data created successfully!');
        $this->table(['Metric', 'Value'], [
            ['Email', $user->email],
            ['Password', 'demo123'],
            ['Sub Users', $user->subUsers()->count()],
            ['User Balance', '$' . number_format($user->balance, 2)],
            ['Total Transactions', $user->transactions()->count()],
            ['URL', 'http://localhost:8000']
        ]);
    }
}
