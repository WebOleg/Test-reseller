<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\SubUser;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_sub_user_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $subUser = SubUser::factory()->for($user)->create();

        $this->assertEquals($user->id, $subUser->user->id);
    }

    public function test_sub_user_has_many_transactions(): void
    {
        $user = User::factory()->create();
        $subUser = SubUser::factory()->for($user)->create();
        
        // Create exactly 3 transactions for this sub user
        Transaction::factory(3)->create([
            'user_id' => $user->id,
            'sub_user_id' => $subUser->id,
        ]);

        // Refresh the model to get updated relationships
        $subUser->refresh();

        $this->assertCount(3, $subUser->transactions);
        $this->assertEquals($user->id, $subUser->transactions->first()->user_id);
    }

    public function test_is_active_returns_correct_status(): void
    {
        $activeSubUser = SubUser::factory()->create(['status' => 'active']);
        $inactiveSubUser = SubUser::factory()->create(['status' => 'inactive']);

        $this->assertTrue($activeSubUser->isActive());
        $this->assertFalse($inactiveSubUser->isActive());
    }

    public function test_balance_is_cast_to_decimal(): void
    {
        $subUser = SubUser::factory()->create(['balance' => 123.45]);

        $this->assertEquals('123.45', $subUser->balance);
        $this->assertIsString($subUser->balance);
    }

    public function test_password_is_hidden(): void
    {
        $subUser = SubUser::factory()->create();

        $this->assertArrayNotHasKey('password', $subUser->toArray());
    }
}
