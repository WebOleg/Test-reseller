<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_processes_payment_webhook_successfully(): void
    {
        $user = User::factory()->create();
        $user->update(['balance' => 100.00]);
        
        $payload = [
            'webhook_id' => 'test_webhook_123',
            'event_type' => 'payment.completed',
            'user_id' => $user->id,
            'amount' => 50.00,
            'transaction_id' => 'tx_test_123'
        ];

        $secret = 'test_secret';
        config(['services.webhook.secret' => $secret]);
        
        $payloadString = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadString, $secret);

        $response = $this->withHeaders([
            'X-Webhook-Signature' => $signature,
            'Content-Type' => 'application/json'
        ])->postJson('/webhook/payment', $payload);

        $response->assertStatus(200);
        
        $user->refresh();
        $this->assertEquals(150.00, $user->balance);
    }

    public function test_rejects_invalid_signature(): void
    {
        $user = User::factory()->create();
        
        $payload = [
            'event_type' => 'payment.completed',
            'user_id' => $user->id,
            'amount' => 50.00
        ];

        $response = $this->withHeaders([
            'X-Webhook-Signature' => 'invalid_signature',
            'Content-Type' => 'application/json'
        ])->postJson('/webhook/payment', $payload);

        $response->assertStatus(403);
    }
}
