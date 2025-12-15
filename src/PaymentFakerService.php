<?php

namespace PaymentFaker;

/**
 * PaymentFakerService - Service wrapper for easier integration
 * 
 * Provides a service interface similar to MyCoolPayService or CinetPayService
 */
class PaymentFakerService
{
    private PaymentFakerClient $client;

    public function __construct(
        string $apiKey = 'test_api_key',
        string $apiSecret = 'test_api_secret',
        string $baseUrl = 'https://faker.payment.test',
        bool $simulateDelays = false,
        float $successRate = 1.0
    ) {
        $this->client = new PaymentFakerClient(
            $apiKey,
            $apiSecret,
            $baseUrl,
            $simulateDelays,
            $successRate
        );
    }

    /**
     * Initiate payment
     * 
     * @param array $paymentData
     * @param string $successUrl
     * @param string $cancelUrl
     * @param string|null $webhookUrl
     * @return array
     */
    public function initiatePayment(
        array $paymentData,
        string $successUrl,
        string $cancelUrl,
        ?string $webhookUrl = null
    ): array {
        return $this->client->initiatePayment($paymentData, $successUrl, $cancelUrl, $webhookUrl);
    }

    /**
     * Check payment status
     * 
     * @param string $transactionRef
     * @return array
     */
    public function checkStatus(string $transactionRef): array
    {
        return $this->client->checkStatus($transactionRef);
    }

    /**
     * Handle webhook (simulates receiving webhook data)
     * 
     * @param array $payload Webhook payload
     * @return bool
     */
    public function handleWebhook(array $payload): bool
    {
        // In a real scenario, you would validate the webhook signature
        // For faker, we just return true
        return isset($payload['transaction_ref']) || isset($payload['transaction_id']);
    }

    /**
     * Get the underlying client (for advanced usage)
     * 
     * @return PaymentFakerClient
     */
    public function getClient(): PaymentFakerClient
    {
        return $this->client;
    }
}

