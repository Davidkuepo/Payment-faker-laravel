# Payment Faker - Examples

## Basic Usage

```php
use PaymentFaker\PaymentFakerClient;

$client = new PaymentFakerClient(
    apiKey: 'test_key',
    apiSecret: 'test_secret'
);

// Initiate payment
$response = $client->initiatePayment(
    paymentData: [
        'transaction_id' => 'TXN-123',
        'amount' => 10000,
        'currency' => 'XOF',
        'description' => 'Test payment',
    ],
    successUrl: 'https://yourapp.com/success',
    cancelUrl: 'https://yourapp.com/cancel',
    webhookUrl: 'https://yourapp.com/webhook'
);

// Approve payment manually
$client->approvePayment($response['transaction_ref'], approve: true);

// Check status
$status = $client->checkStatus($response['transaction_ref']);
```

## Laravel Integration Example

```php
use App\Services\Payment\PaymentFakerService;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentFakerService $paymentFakerService
    ) {}

    public function initiate(Subscription $subscription)
    {
        $response = $this->paymentFakerService->initiatePayment(
            subscription: $subscription,
            paymentMethod: 'card',
            successUrl: route('payment.success'),
            cancelUrl: route('payment.cancel'),
            webhookUrl: route('payment.webhook')
        );

        return response()->json(['redirect_url' => $response['payment_url']]);
    }
}
```

