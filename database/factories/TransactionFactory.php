<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use App\Models\SubUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $types = ['deposit', 'charge', 'refund'];
        $type = $this->faker->randomElement($types);
        
        $descriptions = [
            'deposit' => ['Monthly balance top-up', 'Account funding', 'Payment received'],
            'charge' => ['Proxy traffic usage', 'Data scraping session', 'API usage fee'],
            'refund' => ['Service credit', 'Billing adjustment', 'Refund processed']
        ];
        
        $amounts = [
            'deposit' => [100, 500],
            'charge' => [5, 75], 
            'refund' => [10, 50]
        ];

        return [
            'type' => $type,
            'amount' => $this->faker->randomFloat(2, $amounts[$type][0], $amounts[$type][1]),
            'description' => $this->faker->randomElement($descriptions[$type]),
            'reference_id' => strtoupper($this->faker->bothify('???#####')),
            'status' => $this->faker->randomElement(['completed', 'pending']),
            'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
