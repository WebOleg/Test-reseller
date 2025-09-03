<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DataImpulse API Test ===\n";

// Clear token cache
\Illuminate\Support\Facades\Cache::forget('dataimpulse_token');

$client = app(\App\Services\ResellerApi\ResellerApiClient::class);

echo "1. Testing credentials...\n";
echo "Login: " . config('services.reseller_api.login') . "\n";
echo "Password length: " . strlen(config('services.reseller_api.password')) . " chars\n\n";

echo "2. Testing balance...\n";
try {
    $balance = $client->getUserBalance();
    echo "✓ Balance: " . $balance['balance'] . " bytes\n";
    echo "  That's " . round($balance['balance'] / (1024*1024*1024), 2) . " GB\n\n";
} catch (Exception $e) {
    echo "✗ Balance failed: " . $e->getMessage() . "\n\n";
}

echo "3. Testing sub user creation...\n";
try {
    $username = 'test_' . date('His');
    $result = $client->createSubUser([
        'username' => $username,
        'threads' => 50
    ]);
    echo "✓ Sub user created successfully!\n";
    echo "  ID: " . $result['id'] . "\n";
    echo "  Label: " . $result['label'] . "\n\n";
    
    echo "4. Testing sub user info...\n";
    $info = $client->getSubUser($result['id']);
    echo "✓ Sub user info retrieved:\n";
    print_r($info);
    
} catch (Exception $e) {
    echo "✗ Sub user creation failed: " . $e->getMessage() . "\n\n";
}

echo "5. Testing sub users list...\n";
try {
    $list = $client->listSubUsers(5);
    $count = count($list['data'] ?? $list);
    echo "✓ Found {$count} sub users\n";
} catch (Exception $e) {
    echo "✗ List failed: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
