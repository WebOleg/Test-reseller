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
    protected $signature = 'demo:create {--user=demo@test.com} {--clean}';
    protected $description = 'Create demo data with real API integration';

    private ResellerApiClient $apiClient;

    public function __construct(ResellerApiClient $apiClient)
    {
        parent::__construct();
        $this->apiClient = $apiClient;
    }

    public function handle(): int
    {
        $this->info('Creating demo data with real DataImpulse API...');

        try {
            $apiBalance = $this->apiClient->getUserBalance();
            $this->info("API Balance: {$apiBalance['balance']} bytes");
        } catch (\Exception $e) {
            $this->warn("API Error: {$e->getMessage()}");
            return Command::FAILURE;
        }

        $user = $this->getOrCreateDemoUser();
        
        if ($this->option('clean')) {
            $this->cleanExistingData($user);
        }
        
        $subUsers = $this->createSubUsers($user);
        $this->createTransactions($user, $subUsers);
        $this->updateUserBalance($user);
        $this->displaySummary($user);
        
        return Command::SUCCESS;
    }

    private function getOrCreateDemoUser(): User
    {
        $email = $this->option('user');
        
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'balance' => 100.00,
            ]
        );
        
        $this->info("Using user: {$user->email}");
        return $user;
    }

    private function cleanExistingData(User $user): void
    {
        $this->info('Cleaning existing data...');
        Transaction::where('user_id', $user->id)->delete();
        SubUser::where('user_id', $user->id)->delete();
        $user->update(['balance' => 100.00]);
    }

    private function createSubUsers(User $user): \Illuminate\Support\Collection
    {
        $this->info('Creating sub users with API integration...');
        
        $demoUsers = [
            ['username' => 'marketing_' . time(), 'email' => 'marketing@test.com', 'balance' => 250.50],
            ['username' => 'seo_' . time(), 'email' => 'seo@test.com', 'balance' => 180.75],
            ['username' => 'scraping_' . time(), 'email' => 'data@test.com', 'balance' => 95.25],
        ];

        $subUsers = collect();
        
        foreach ($demoUsers as $userData) {
            $subUser = SubUser::create([
                'user_id' => $user->id,
                'username' => $userData['username'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'balance' => $userData['balance'],
                'status' => 'active'
            ]);

            try {
                $apiResponse = $this->apiClient->createSubUser([
                    'username' => $userData['username'],
                    'threads' => rand(30, 100)
                ]);
                
                $subUser->update(['api_user_id' => $apiResponse['id']]);
                
                $this->info("✓ {$userData['username']} - API ID: {$apiResponse['id']}");
                $this->line("  Proxy: {$apiResponse['login']}:{$apiResponse['password']}");
                
            } catch (\Exception $e) {
                $this->warn("✗ {$userData['username']} - API failed: " . substr($e->getMessage(), 0, 50));
            }
            
            $subUsers->push($subUser);
        }
        
        return $subUsers;
    }

    private function createTransactions(User $user, $subUsers): void
    {
        $this->info('Creating transactions...');
        
        for ($i = 0; $i < 20; $i++) {
            Transaction::factory()->create([
                'user_id' => $user->id,
                'sub_user_id' => $subUsers->random()->id,
                'created_at' => now()->subDays(rand(1, 30))
            ]);
        }
        
        Transaction::factory(5)->create([
            'user_id' => $user->id,
            'sub_user_id' => null,
            'type' => 'deposit',
            'created_at' => now()->subDays(rand(1, 15))
        ]);
    }

    private function updateUserBalance(User $user): void
    {
        $totalDeposits = $user->transactions()->where('type', 'deposit')->sum('amount');
        $totalSpent = $user->transactions()->where('type', 'charge')->sum('amount');
        $balance = max(100, $totalDeposits - $totalSpent + 100);
        
        $user->update(['balance' => $balance]);
    }

    private function displaySummary(User $user): void
    {
        $this->newLine();
        $this->info('Demo data created successfully!');
        
        $this->table(['Metric', 'Value'], [
            ['Email', $user->email],
            ['Password', 'password'],
            ['Sub Users', $user->subUsers()->count()],
            ['API Synced', $user->subUsers()->whereNotNull('api_user_id')->count()],
            ['User Balance', '$' . number_format($user->balance, 2)],
            ['Transactions', $user->transactions()->count()],
            ['URL', 'http://localhost:8000']
        ]);
        
        $this->newLine();
        $this->info('Login at: http://localhost:8000');
        $this->info('Email: ' . $user->email);
        $this->info('Password: password');
    }
}
