# Payment Faker

A fake payment provider library for testing payment flows. Simulates the behavior of payment providers like MyCoolPay or CinetPay without making real API calls.

## Features

- ✅ Simulate payment initiation
- ✅ Simulate payment approval/rejection
- ✅ Check payment status
- ✅ Webhook simulation
- ✅ Configurable success rate
- ✅ Optional network delay simulation
- ✅ Transaction history management

## Installation

### Standalone (outside of Composer project)

```bash
cd /path/to/payment-faker
composer install
```

### In a Laravel/Composer project

Add to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../payment-faker"
        }
    ],
    "require": {
        "restaurant-web/payment-faker": "*"
    }
}
```

Then run:
```bash
composer require your-app/payment-faker
```

## Usage

### Basic Usage

```php
use PaymentFaker\PaymentFakerClient;

$client = new PaymentFakerClient(
    apiKey: 'test_api_key',
    apiSecret: 'test_api_secret',
    baseUrl: 'https://faker.payment.test',
    simulateDelays: false,
    successRate: 1.0 // 100% success rate
);

// Initiate payment
$paymentData = [
    'transaction_id' => 'TXN-' . time(),
    'amount' => 10000,
    'currency' => 'XOF',
    'description' => 'Test payment',
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
];

$response = $client->initiatePayment(
    paymentData: $paymentData,
    successUrl: 'https://yourapp.com/payment/success',
    cancelUrl: 'https://yourapp.com/payment/cancel',
    webhookUrl: 'https://yourapp.com/payment/webhook'
);

// $response contains:
// [
//     'transaction_ref' => 'TXN-1234567890',
//     'payment_url' => 'https://faker.payment.test/payment/checkout?token=...',
//     'status' => 'success',
//     'message' => 'Payment initiated successfully'
// ]

// Check status
$status = $client->checkStatus($response['transaction_ref']);

// Manually approve payment (for testing)
$client->approvePayment($response['transaction_ref'], approve: true);
```

### Advanced Usage

```php
use PaymentFaker\PaymentFakerClient;

// Configure with custom success rate and delays
$client = new PaymentFakerClient(
    apiKey: 'test_key',
    apiSecret: 'test_secret',
    baseUrl: 'https://faker.payment.test',
    simulateDelays: true, // Simulate network delays
    successRate: 0.95, // 95% success rate
    delayMinMs: 100,
    delayMaxMs: 500
);

// Get all transactions
$transactions = $client->getAllTransactions();

// Get webhook payload
$webhookPayload = $client->getWebhookPayload('TXN-123');

// Clear all transactions (for testing)
$client->clearTransactions();
```

### Integration with Laravel

Create a service provider:

```php
// app/Providers/PaymentFakerServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use PaymentFaker\PaymentFakerService;

class PaymentFakerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentFakerService::class, function ($app) {
            return new PaymentFakerService(
                apiKey: config('services.paymentfaker.api_key', 'test_key'),
                apiSecret: config('services.paymentfaker.api_secret', 'test_secret'),
                baseUrl: config('services.paymentfaker.base_url', 'https://faker.payment.test'),
                simulateDelays: config('services.paymentfaker.simulate_delays', false),
                successRate: config('services.paymentfaker.success_rate', 1.0)
            );
        });
    }
}
```

## Configuration

### Environment Variables (Laravel)

```env
PAYMENT_FAKER_API_KEY=test_api_key
PAYMENT_FAKER_API_SECRET=test_api_secret
PAYMENT_FAKER_BASE_URL=https://faker.payment.test
PAYMENT_FAKER_SIMULATE_DELAYS=false
PAYMENT_FAKER_SUCCESS_RATE=1.0
```

### Config File (Laravel)

```php
// config/services.php
'paymentfaker' => [
    'api_key' => env('PAYMENT_FAKER_API_KEY', 'test_key'),
    'api_secret' => env('PAYMENT_FAKER_API_SECRET', 'test_secret'),
    'base_url' => env('PAYMENT_FAKER_BASE_URL', 'https://faker.payment.test'),
    'simulate_delays' => env('PAYMENT_FAKER_SIMULATE_DELAYS', false),
    'success_rate' => env('PAYMENT_FAKER_SUCCESS_RATE', 1.0),
],
```

## API Reference

### PaymentFakerClient

#### `initiatePayment(array $paymentData, string $successUrl, string $cancelUrl, ?string $webhookUrl = null): array`

Initiate a new payment transaction.

#### `approvePayment(string $transactionRef, ?bool $approve = null): array`

Manually approve or reject a payment. If `$approve` is null, uses the configured success rate.

#### `cancelPayment(string $transactionRef): array`

Cancel a pending payment.

#### `checkStatus(string $transactionRef): array`

Check the status of a payment transaction.

#### `getTransaction(string $transactionRef): array`

Get full transaction details.

#### `getAllTransactions(): array`

Get all stored transactions.

#### `clearTransactions(): void`

Clear all transactions (useful for testing).

#### `triggerWebhook(string $transactionRef): bool`

Trigger webhook callback for a transaction.

#### `getWebhookPayload(string $transactionRef): ?array`

Get webhook payload data for a transaction.

## Testing

Run tests:

```bash
composer test
```

## License

MIT

