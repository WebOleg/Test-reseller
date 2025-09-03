<?php

namespace App\Services\ResellerApi;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ResellerApiClient
{
    protected string $baseUrl;
    protected string $login;
    protected string $password;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = 'https://api.dataimpulse.com/reseller';
        $this->login = config('services.reseller_api.login');
        $this->password = config('services.reseller_api.password');
        $this->timeout = config('services.reseller_api.timeout', 30);
    }

    /**
     * Get authentication token
     */
    protected function getToken(): string
    {
        return Cache::remember('dataimpulse_token', 23 * 60 * 60, function () {
            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/user/token/get', [
                    'login' => $this->login,
                    'password' => $this->password,
                ]);

            if ($response->failed()) {
                throw new \Exception('Failed to get API token: ' . $response->body());
            }

            $data = $response->json();
            return $data['token'] ?? throw new \Exception('No token in response');
        });
    }

    /**
     * Create sub user via DataImpulse API
     */
    public function createSubUser(array $data): array
    {
        $response = $this->makeRequest('POST', '/sub-user/create', [
            'label' => $data['username'] ?? $data['label'],
            'threads' => $data['threads'] ?? 50,
            'allowed_ips' => $data['allowed_ips'] ?? [],
            'sticky_range' => [
                'start' => 10000,
                'end' => 20000,
            ],
        ]);

        return $response->json();
    }

    /**
     * Update sub user via DataImpulse API
     */
    public function updateSubUser(int $subuserId, array $data): array
    {
        $payload = ['subuser_id' => $subuserId];
        
        if (isset($data['username'])) {
            $payload['label'] = $data['username'];
        }
        if (isset($data['threads'])) {
            $payload['threads'] = $data['threads'];
        }

        $response = $this->makeRequest('POST', '/sub-user/update', $payload);
        return $response->json();
    }

    /**
     * Delete sub user via DataImpulse API
     */
    public function deleteSubUser(int $subuserId): bool
    {
        $response = $this->makeRequest('POST', '/sub-user/delete', [
            'subuser_id' => $subuserId
        ]);

        return $response->successful();
    }

    /**
     * Get sub user info via DataImpulse API
     */
    public function getSubUser(int $subuserId): array
    {
        $response = $this->makeRequest('GET', '/sub-user/get', [
            'subuser_id' => $subuserId
        ]);

        return $response->json();
    }

    /**
     * Get all sub users from DataImpulse API
     */
    public function listSubUsers(int $limit = 100, int $offset = 0): array
    {
        $response = $this->makeRequest('GET', '/sub-user/list', [
            'limit' => $limit,
            'offset' => $offset,
        ]);

        return $response->json();
    }

    /**
     * Get sub user balance
     */
    public function getSubUserBalance(int $subuserId): array
    {
        $response = $this->makeRequest('GET', '/sub-user/balance/get', [
            'subuser_id' => $subuserId
        ]);

        return $response->json();
    }

    /**
     * Add balance to sub user
     */
    public function addSubUserBalance(int $subuserId, int $traffic): array
    {
        $response = $this->makeRequest('POST', '/sub-user/balance/add', [
            'subuser_id' => $subuserId,
            'traffic' => $traffic,
        ]);

        return $response->json();
    }

    /**
     * Get user balance
     */
    public function getUserBalance(): array
    {
        $response = $this->makeRequest('GET', '/user/balance');
        return $response->json();
    }

    /**
     * Make HTTP request to DataImpulse API
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): Response
    {
        $token = $this->getToken();
        $url = $this->baseUrl . $endpoint;

        Log::info('DataImpulse API Request', [
            'method' => $method,
            'url' => $url,
            'data' => $data
        ]);

        $request = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout($this->timeout);

        $response = $method === 'GET' 
            ? $request->get($url, $data)
            : $request->$method($url, $data);

        Log::info('DataImpulse API Response', [
            'status' => $response->status(),
            'body' => $response->json()
        ]);

        if ($response->failed()) {
            Log::error('DataImpulse API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            throw new \Exception("DataImpulse API request failed: " . $response->body());
        }

        return $response;
    }
}
