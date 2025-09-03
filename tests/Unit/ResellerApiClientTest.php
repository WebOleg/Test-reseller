<?php

namespace Tests\Unit;

use App\Services\ResellerApi\ResellerApiClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ResellerApiClientTest extends TestCase
{
    protected ResellerApiClient $apiClient;

    protected function setUp(): void
    {
        parent::setUp();
        
        config([
            'services.reseller_api.login' => 'test@example.com',
            'services.reseller_api.password' => 'testpassword',
            'services.reseller_api.timeout' => 30,
        ]);

        $this->apiClient = new ResellerApiClient();
    }

    public function test_can_get_user_balance(): void
    {
        Http::fake([
            'https://api.dataimpulse.com/reseller/user/token/get' => Http::response([
                'token' => 'test_token_123456789'
            ]),
            'https://api.dataimpulse.com/reseller/user/balance' => Http::response([
                'balance' => 5000000000,
                'currency' => 'bytes'
            ])
        ]);

        $balance = $this->apiClient->getUserBalance();

        $this->assertEquals(5000000000, $balance['balance']);
    }

    public function test_can_create_sub_user(): void
    {
        Http::fake([
            'https://api.dataimpulse.com/reseller/user/token/get' => Http::response([
                'token' => 'test_token_123456789'
            ]),
            'https://api.dataimpulse.com/reseller/sub-user/create' => Http::response([
                'id' => 12345,
                'label' => 'testuser',
                'status' => 'active'
            ])
        ]);

        $result = $this->apiClient->createSubUser([
            'username' => 'testuser',
        ]);

        $this->assertEquals(12345, $result['id']);
        $this->assertEquals('testuser', $result['label']);
    }

    public function test_handles_api_errors_gracefully(): void
    {
        Http::fake([
            'https://api.dataimpulse.com/reseller/user/token/get' => Http::response([
                'token' => 'test_token_123456789'
            ]),
            'https://api.dataimpulse.com/reseller/user/balance' => Http::response(
                ['error' => 'Unauthorized'], 401
            )
        ]);

        $this->expectException(\Exception::class);
        $this->apiClient->getUserBalance();
    }
}
