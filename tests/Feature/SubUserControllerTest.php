<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SubUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubUserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_view_sub_users_index(): void
    {
        $subUsers = SubUser::factory(3)->for($this->user)->create();

        $response = $this->get(route('sub-users.index'));

        $response->assertStatus(200);
        $response->assertViewIs('sub-users.index');
        $response->assertViewHas('subUsers');
    }

    public function test_can_search_sub_users(): void
    {
        $subUser1 = SubUser::factory()->for($this->user)->create(['username' => 'testuser']);
        $subUser2 = SubUser::factory()->for($this->user)->create(['username' => 'otheruser']);

        $response = $this->get(route('sub-users.index', ['search' => 'test']));

        $response->assertStatus(200);
        $response->assertSee('testuser');
        $response->assertDontSee('otheruser');
    }

    public function test_can_filter_sub_users_by_status(): void
    {
        $activeUser = SubUser::factory()->for($this->user)->create(['status' => 'active']);
        $inactiveUser = SubUser::factory()->for($this->user)->create(['status' => 'inactive']);

        $response = $this->get(route('sub-users.index', ['status' => 'active']));

        $response->assertStatus(200);
        $response->assertSee($activeUser->username);
        $response->assertDontSee($inactiveUser->username);
    }

    public function test_can_create_sub_user_without_api(): void
    {
        $userData = [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'balance' => 50.00,
        ];

        $response = $this->post(route('sub-users.store'), $userData);

        $response->assertRedirect(route('sub-users.index'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('sub_users', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_cannot_view_other_users_sub_users(): void
    {
        $otherUser = User::factory()->create();
        $otherSubUser = SubUser::factory()->for($otherUser)->create();

        $response = $this->get(route('sub-users.show', $otherSubUser));

        $response->assertStatus(403);
    }

    public function test_can_update_sub_user(): void
    {
        $subUser = SubUser::factory()->for($this->user)->create();

        $updateData = [
            'username' => 'updateduser',
            'email' => 'updated@example.com',
            'balance' => 75.00,
            'status' => 'active',
        ];

        $response = $this->put(route('sub-users.update', $subUser), $updateData);

        $response->assertRedirect(route('sub-users.index'));
        $response->assertSessionHas('success');
        
        $subUser->refresh();
        $this->assertEquals('updateduser', $subUser->username);
        $this->assertEquals('updated@example.com', $subUser->email);
    }

    public function test_validation_errors_on_duplicate_username(): void
    {
        $existingUser = SubUser::factory()->for($this->user)->create(['username' => 'existing']);

        $userData = [
            'username' => 'existing',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('sub-users.store'), $userData);

        $response->assertSessionHasErrors('username');
    }
}
