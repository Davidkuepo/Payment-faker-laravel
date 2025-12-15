<?php

namespace PaymentFaker;

use Exception;

/**
 * PaymentFakerClient - A fake payment provider for testing
 * 
 * Simulates the behavior of payment providers like MyCoolPay or CinetPay
 * for testing purposes without making real API calls.
 */
class PaymentFakerClient
{
    private string $apiKey;
    private string $apiSecret;
    private string $baseUrl;
    private array $transactions = [];
    private bool $simulateDelays;
    private float $successRate; // 0.0 to 1.0 (e.g., 0.95 = 95% success rate)
    private int $delayMinMs;
    private int $delayMaxMs;

    public function __construct(
        string $apiKey,
        string $apiSecret,
        string $baseUrl = 'https://faker.payment.test',
        bool $simulateDelays = false,
        float $successRate = 1.0,
        int $delayMinMs = 100,
        int $delayMaxMs = 500
    ) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->simulateDelays = $simulateDelays;
        $this->successRate = max(0.0, min(1.0, $successRate)); // Clamp between 0 and 1
        $this->delayMinMs = $delayMinMs;
        $this->delayMaxMs = $delayMaxMs;
    }

    /**
     * Initiate a payment transaction
     * 
     * @param array $paymentData Payment data (amount, currency, description, etc.)
     * @param string $successUrl URL to redirect after successful payment
     * @param string $cancelUrl URL to redirect if payment is cancelled
     * @param string|null $webhookUrl URL to send payment notifications
     * @return array Response containing transaction_ref and payment_url
     * @throws Exception
     */
    public function initiatePayment(
        array $paymentData,
        string $successUrl,
        string $cancelUrl,
        ?string $webhookUrl = null
    ): array {
        $this->simulateDelay();

        // Validate required fields
        if (empty($paymentData['amount']) || $paymentData['amount'] <= 0) {
            throw new Exception('Invalid payment amount');
        }

        if (empty($paymentData['transaction_id'])) {
            throw new Exception('transaction_id is required');
        }

        // Generate transaction reference
        $transactionRef = $paymentData['transaction_id'];
        
        // Generate payment URL (simulated)
        $paymentToken = $this->generateToken();
        // Use /payment-faker/checkout route if baseUrl is the app URL, otherwise use /payment/checkout
        $checkoutPath = str_contains($this->baseUrl, 'faker.payment.test') 
            ? '/payment/checkout' 
            : '/payment-faker/checkout';
        $paymentUrl = $this->baseUrl . $checkoutPath . '?token=' . $paymentToken;

        // Store transaction
        $transaction = [
            'transaction_ref' => $transactionRef,
            'transaction_id' => $transactionRef,
            'payment_token' => $paymentToken,
            'amount' => (float) $paymentData['amount'],
            'currency' => $paymentData['currency'] ?? 'XOF',
            'description' => $paymentData['description'] ?? 'Payment',
            'customer_name' => $paymentData['customer_name'] ?? 'Test Customer',
            'customer_email' => $paymentData['customer_email'] ?? null,
            'customer_phone' => $paymentData['customer_phone_number'] ?? null,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'webhook_url' => $webhookUrl,
            'status' => 'PENDING',
            'created_at' => time(),
            'updated_at' => time(),
        ];

        $this->transactions[$transactionRef] = $transaction;

        return [
            'transaction_ref' => $transactionRef,
            'transaction_id' => $transactionRef,
            'payment_url' => $paymentUrl,
            'payment_token' => $paymentToken,
            'status' => 'success',
            'message' => 'Payment initiated successfully',
            'code' => '201',
        ];
    }

    /**
     * Simulate payment approval (for testing)
     * This is called internally or can be called manually for testing
     * 
     * @param string $transactionRef Transaction reference
     * @param bool|null $approve If null, uses success rate probability
     * @return array Updated transaction data
     * @throws Exception
     */
    public function approvePayment(string $transactionRef, ?bool $approve = null): array
    {
        if (!isset($this->transactions[$transactionRef])) {
            throw new Exception('Transaction not found: ' . $transactionRef);
        }

        $transaction = &$this->transactions[$transactionRef];

        if ($transaction['status'] !== 'PENDING') {
            throw new Exception('Transaction is not in PENDING status');
        }

        // Determine approval based on success rate if not explicitly set
        if ($approve === null) {
            $approve = (mt_rand() / mt_getrandmax()) <= $this->successRate;
        }

        $transaction['status'] = $approve ? 'COMPLETED' : 'FAILED';
        $transaction['updated_at'] = time();
        $transaction['completed_at'] = time();

        // Simulate webhook callback if webhook URL is provided
        if ($transaction['webhook_url']) {
            $this->triggerWebhook($transactionRef);
        }

        return $transaction;
    }

    /**
     * Simulate payment cancellation
     * 
     * @param string $transactionRef Transaction reference
     * @return array Updated transaction data
     * @throws Exception
     */
    public function cancelPayment(string $transactionRef): array
    {
        if (!isset($this->transactions[$transactionRef])) {
            throw new Exception('Transaction not found: ' . $transactionRef);
        }

        $transaction = &$this->transactions[$transactionRef];

        if ($transaction['status'] !== 'PENDING') {
            throw new Exception('Transaction is not in PENDING status');
        }

        $transaction['status'] = 'CANCELLED';
        $transaction['updated_at'] = time();

        return $transaction;
    }

    /**
     * Check payment status
     * 
     * @param string $transactionRef Transaction reference
     * @return array Transaction status data
     * @throws Exception
     */
    public function checkStatus(string $transactionRef): array
    {
        $this->simulateDelay();

        if (!isset($this->transactions[$transactionRef])) {
            throw new Exception('Transaction not found: ' . $transactionRef);
        }

        $transaction = $this->transactions[$transactionRef];

        // Map internal status to API response format
        $statusMap = [
            'PENDING' => 'pending',
            'COMPLETED' => 'completed',
            'FAILED' => 'failed',
            'CANCELLED' => 'cancelled',
        ];

        return [
            'transaction_ref' => $transactionRef,
            'transaction_id' => $transactionRef,
            'status' => $statusMap[$transaction['status']] ?? 'unknown',
            'amount' => $transaction['amount'],
            'currency' => $transaction['currency'],
            'message' => $this->getStatusMessage($transaction['status']),
            'data' => $transaction,
        ];
    }

    /**
     * Get transaction details
     * 
     * @param string $transactionRef Transaction reference
     * @return array Transaction data
     * @throws Exception
     */
    public function getTransaction(string $transactionRef): array
    {
        if (!isset($this->transactions[$transactionRef])) {
            throw new Exception('Transaction not found: ' . $transactionRef);
        }

        return $this->transactions[$transactionRef];
    }

    /**
     * Get all transactions (for testing/debugging)
     * 
     * @return array All transactions
     */
    public function getAllTransactions(): array
    {
        return $this->transactions;
    }

    /**
     * Clear all transactions (for testing)
     */
    public function clearTransactions(): void
    {
        $this->transactions = [];
    }

    /**
     * Trigger webhook callback (simulated)
     * In a real scenario, this would make an HTTP request to the webhook URL
     * 
     * @param string $transactionRef Transaction reference
     * @return bool Success status
     */
    public function triggerWebhook(string $transactionRef): bool
    {
        if (!isset($this->transactions[$transactionRef])) {
            return false;
        }

        $transaction = $this->transactions[$transactionRef];

        // In a real implementation, you would make an HTTP POST request here
        // For now, we just return true to indicate the webhook was "sent"
        
        // Example of what would be sent:
        $webhookData = [
            'transaction_ref' => $transactionRef,
            'transaction_id' => $transactionRef,
            'status' => strtolower($transaction['status']),
            'amount' => $transaction['amount'],
            'currency' => $transaction['currency'],
            'timestamp' => $transaction['updated_at'],
        ];

        // You can implement actual HTTP request here if needed
        // For testing, we'll just log it conceptually

        return true;
    }

    /**
     * Get webhook payload for a transaction
     * Useful for testing webhook handlers
     * 
     * @param string $transactionRef Transaction reference
     * @return array|null Webhook payload or null if transaction not found
     */
    public function getWebhookPayload(string $transactionRef): ?array
    {
        if (!isset($this->transactions[$transactionRef])) {
            return null;
        }

        $transaction = $this->transactions[$transactionRef];

        return [
            'transaction_ref' => $transactionRef,
            'transaction_id' => $transactionRef,
            'status' => strtolower($transaction['status']),
            'amount' => $transaction['amount'],
            'currency' => $transaction['currency'],
            'message' => $this->getStatusMessage($transaction['status']),
            'timestamp' => $transaction['updated_at'] ?? time(),
        ];
    }

    /**
     * Generate a random token
     */
    private function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Get status message
     */
    private function getStatusMessage(string $status): string
    {
        $messages = [
            'PENDING' => 'Payment is pending',
            'COMPLETED' => 'Payment completed successfully',
            'FAILED' => 'Payment failed',
            'CANCELLED' => 'Payment was cancelled',
        ];

        return $messages[$status] ?? 'Unknown status';
    }

    /**
     * Simulate network delay (optional)
     */
    private function simulateDelay(): void
    {
        if ($this->simulateDelays) {
            $delayMs = mt_rand($this->delayMinMs, $this->delayMaxMs);
            usleep($delayMs * 1000); // Convert to microseconds
        }
    }

    /**
     * Set success rate for automatic approval
     * 
     * @param float $rate Success rate between 0.0 and 1.0
     */
    public function setSuccessRate(float $rate): void
    {
        $this->successRate = max(0.0, min(1.0, $rate));
    }

    /**
     * Get current success rate
     * 
     * @return float Success rate
     */
    public function getSuccessRate(): float
    {
        return $this->successRate;
    }
}

