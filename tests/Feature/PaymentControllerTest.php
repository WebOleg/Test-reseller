<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->update(['balance' => 100.00]);
        $this->actingAs($this->user);
    }

    public function test_can_view_payment_form(): void
    {
        $response = $this->get(route('payment.form'));
        
        $response->assertStatus(200);
        $response->assertViewIs('payments.form');
    }

    public function test_can_process_successful_payment(): void
    {
        $response = $this->post(route('payment.process'), [
            'amount' => 50.00,
            'payment_method' => 'card'
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'deposit',
            'amount' => 50.00
        ]);
    }

    public function test_validates_payment_amount(): void
    {
        $response = $this->post(route('payment.process'), [
            'amount' => 0,
            'payment_method' => 'card'
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_validates_payment_method(): void
    {
        $response = $this->post(route('payment.process'), [
            'amount' => 50.00,
            'payment_method' => ''
        ]);

        $response->assertSessionHasErrors('payment_method');
    }
}
