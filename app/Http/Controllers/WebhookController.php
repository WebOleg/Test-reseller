<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    /**
     * Handle payment webhook from external service
     */
    public function handlePayment(Request $request)
    {
        try {
            // Verify webhook signature
            if (!$this->verifySignature($request)) {
                Log::warning('Webhook signature verification failed', [
                    'headers' => $request->headers->all(),
                    'payload' => $request->getContent()
                ]);
                
                return response()->json(['error' => 'Invalid signature'], 403);
            }

            $payload = $request->json()->all();
            $webhookId = $payload['webhook_id'] ?? uniqid();

            // Check for duplicate webhook (idempotency)
            if ($this->isDuplicateWebhook($webhookId)) {
                Log::info('Duplicate webhook received', ['webhook_id' => $webhookId]);
                return response()->json(['message' => 'Webhook already processed'], 200);
            }

            // Log webhook received
            Log::info('Payment webhook received', [
                'webhook_id' => $webhookId,
                'payload' => $payload
            ]);

            DB::beginTransaction();

            // Process payment based on event type
            $result = $this->processPaymentEvent($payload);

            // Log successful processing
            $this->logWebhook($webhookId, $payload, 'processed');

            DB::commit();

            return response()->json(['message' => 'Webhook processed successfully'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->json()->all()
            ]);

            // Log failed processing
            $this->logWebhook($webhookId ?? uniqid(), $request->json()->all(), 'failed', $e->getMessage());

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Verify webhook signature
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Webhook-Signature');
        $payload = $request->getContent();
        $secret = config('services.webhook.secret');

        if (!$signature || !$secret) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Check if webhook is duplicate
     */
    protected function isDuplicateWebhook(string $webhookId): bool
    {
        return DB::table('payment_webhooks')
            ->where('webhook_id', $webhookId)
            ->exists();
    }

    /**
     * Process payment event based on type
     */
    protected function processPaymentEvent(array $payload): array
    {
        $eventType = $payload['event_type'] ?? 'payment.completed';
        $amount = (float) ($payload['amount'] ?? 0);
        $userId = (int) ($payload['user_id'] ?? 0);

        $user = User::find($userId);
        if (!$user) {
            throw new \Exception("User not found: {$userId}");
        }

        switch ($eventType) {
            case 'payment.completed':
                return $this->handlePaymentCompleted($user, $amount, $payload);
            
            case 'payment.failed':
                return $this->handlePaymentFailed($user, $amount, $payload);
                
            case 'refund.processed':
                return $this->handleRefundProcessed($user, $amount, $payload);
                
            default:
                Log::warning('Unknown event type', ['event_type' => $eventType]);
                return ['status' => 'ignored', 'reason' => 'Unknown event type'];
        }
    }

    /**
     * Handle successful payment
     */
    protected function handlePaymentCompleted(User $user, float $amount, array $payload): array
    {
        $balanceBefore = $user->balance ?? 0;
        $balanceAfter = $balanceBefore + $amount;

        // Update user balance
        $user->update(['balance' => $balanceAfter]);

        // Create transaction record
        Transaction::create([
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => $amount,
            'description' => 'Payment received via webhook',
            'reference_id' => $payload['transaction_id'] ?? null,
        ]);

        Log::info('Payment completed', [
            'user_id' => $user->id,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter
        ]);

        return [
            'status' => 'completed',
            'user_id' => $user->id,
            'amount' => $amount,
            'new_balance' => $balanceAfter
        ];
    }

    /**
     * Handle failed payment
     */
    protected function handlePaymentFailed(User $user, float $amount, array $payload): array
    {
        // Create failed transaction record
        Transaction::create([
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => $amount,
            'description' => 'Failed payment via webhook',
            'reference_id' => $payload['transaction_id'] ?? null,
            'status' => 'failed',
        ]);

        Log::info('Payment failed', [
            'user_id' => $user->id,
            'amount' => $amount,
            'reason' => $payload['failure_reason'] ?? 'Unknown'
        ]);

        return [
            'status' => 'failed',
            'user_id' => $user->id,
            'amount' => $amount
        ];
    }

    /**
     * Handle refund processed
     */
    protected function handleRefundProcessed(User $user, float $amount, array $payload): array
    {
        $balanceBefore = $user->balance ?? 0;
        $balanceAfter = $balanceBefore - $amount;

        // Update user balance
        $user->update(['balance' => max(0, $balanceAfter)]);

        // Create refund transaction record
        Transaction::create([
            'user_id' => $user->id,
            'type' => 'refund',
            'amount' => -$amount,
            'description' => 'Refund processed via webhook',
            'reference_id' => $payload['transaction_id'] ?? null,
        ]);

        Log::info('Refund processed', [
            'user_id' => $user->id,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => max(0, $balanceAfter)
        ]);

        return [
            'status' => 'refunded',
            'user_id' => $user->id,
            'amount' => $amount,
            'new_balance' => max(0, $balanceAfter)
        ];
    }

    /**
     * Log webhook processing
     */
    protected function logWebhook(string $webhookId, array $payload, string $status, ?string $error = null): void
    {
        DB::table('payment_webhooks')->insert([
            'webhook_id' => $webhookId,
            'event_type' => $payload['event_type'] ?? 'unknown',
            'payload' => json_encode($payload),
            'signature' => request()->header('X-Webhook-Signature'),
            'status' => $status,
            'error_message' => $error,
            'processed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
