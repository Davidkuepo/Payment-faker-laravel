# Payment Faker

Une bibliothèque de simulation de passerelle de paiement pour tester les flux de paiement. Simule le comportement de passerelles de paiement comme MyCoolPay ou CinetPay sans effectuer de vrais appels API.

## Fonctionnalités

- ✅ Simulation d'initiation de paiement
- ✅ Simulation d'approbation/rejet de paiement
- ✅ Vérification du statut de paiement
- ✅ Simulation de webhook
- ✅ Taux de succès configurable
- ✅ Simulation optionnelle de délais réseau
- ✅ Gestion de l'historique des transactions

## Installation

### Via Composer

Ajoutez le repository à votre fichier `composer.json` :

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Davidkuepo/Payment-faker-laravel.git"
        }
    ],
    "require": {
        "restaurant-web/payment-faker": "@dev"
    }
}
```

Puis exécutez :

```bash
composer require restaurant-web/payment-faker:@dev
```

### Installation locale (développement)

Si vous clonez le repository localement :

```bash
git clone https://github.com/Davidkuepo/Payment-faker-laravel.git payment-faker
cd payment-faker
composer install
```

Pour l'utiliser dans un projet local, ajoutez le repository path dans votre `composer.json` :

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

Puis :

```bash
composer require restaurant-web/payment-faker
```

## Utilisation

### Utilisation de base

```php
use PaymentFaker\PaymentFakerClient;

// Initialiser le client
$client = new PaymentFakerClient(
    apiKey: 'test_api_key',
    apiSecret: 'test_api_secret',
    baseUrl: 'https://votre-domaine.com',
    simulateDelays: false,
    successRate: 1.0 // 100% de taux de succès
);

// Préparer les données de paiement
$paymentData = [
    'transaction_id' => 'TXN-' . time(),
    'amount' => 10000,
    'currency' => 'XOF',
    'description' => 'Paiement de test',
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
    'customer_phone_number' => '+237 6XX XX XX XX',
];

// Initier un paiement
$response = $client->initiatePayment(
    paymentData: $paymentData,
    successUrl: 'https://votre-app.com/payment/success',
    cancelUrl: 'https://votre-app.com/payment/cancel',
    webhookUrl: 'https://votre-app.com/payment/webhook'
);

// $response contient:
// [
//     'transaction_ref' => 'TXN-1234567890',
//     'payment_url' => 'https://votre-domaine.com/payment-faker/checkout?token=...',
//     'status' => 'success',
//     'message' => 'Payment initiated successfully'
// ]

// Vérifier le statut
$status = $client->checkStatus($response['transaction_ref']);

// Approuver manuellement un paiement (pour les tests)
$client->approvePayment($response['transaction_ref'], approve: true);
```

### Utilisation avancée

```php
use PaymentFaker\PaymentFakerClient;

// Configuration avec taux de succès personnalisé et délais
$client = new PaymentFakerClient(
    apiKey: 'test_key',
    apiSecret: 'test_secret',
    baseUrl: 'https://votre-domaine.com',
    simulateDelays: true, // Simuler les délais réseau
    successRate: 0.95, // 95% de taux de succès
    delayMinMs: 100,
    delayMaxMs: 500
);

// Obtenir toutes les transactions
$transactions = $client->getAllTransactions();

// Obtenir le payload du webhook
$webhookPayload = $client->getWebhookPayload('TXN-123');

// Vider toutes les transactions (pour les tests)
$client->clearTransactions();
```

### Intégration avec Laravel

Créez un service provider :

```php
// app/Providers/PaymentFakerServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use PaymentFaker\PaymentFakerClient;

class PaymentFakerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentFakerClient::class, function ($app) {
            return new PaymentFakerClient(
                apiKey: config('services.paymentfaker.api_key', 'test_key'),
                apiSecret: config('services.paymentfaker.api_secret', 'test_secret'),
                baseUrl: config('services.paymentfaker.base_url', 'https://votre-domaine.com'),
                simulateDelays: config('services.paymentfaker.simulate_delays', false),
                successRate: config('services.paymentfaker.success_rate', 1.0)
            );
        });
    }
}
```

Enregistrez le service provider dans `config/app.php` :

```php
'providers' => [
    // ...
    App\Providers\PaymentFakerServiceProvider::class,
],
```

## Configuration

### Variables d'environnement (Laravel)

Ajoutez dans votre fichier `.env` :

```env
PAYMENT_FAKER_API_KEY=test_api_key
PAYMENT_FAKER_API_SECRET=test_api_secret
PAYMENT_FAKER_BASE_URL=https://votre-domaine.com
PAYMENT_FAKER_SIMULATE_DELAYS=false
PAYMENT_FAKER_SUCCESS_RATE=1.0
```

### Fichier de configuration (Laravel)

```php
// config/services.php
'paymentfaker' => [
    'api_key' => env('PAYMENT_FAKER_API_KEY', 'test_key'),
    'api_secret' => env('PAYMENT_FAKER_API_SECRET', 'test_secret'),
    'base_url' => env('PAYMENT_FAKER_BASE_URL', 'https://votre-domaine.com'),
    'simulate_delays' => env('PAYMENT_FAKER_SIMULATE_DELAYS', false),
    'success_rate' => env('PAYMENT_FAKER_SUCCESS_RATE', 1.0),
],
```

## Routes nécessaires (Laravel)

Pour utiliser cette bibliothèque dans Laravel, vous devez créer des routes pour gérer le checkout :

```php
// routes/web.php

// Route pour la page de checkout simulée
Route::get('/payment-faker/checkout', [PaymentFakerController::class, 'checkout'])
    ->name('payment-faker.checkout');

// Route pour traiter l'approbation/annulation
Route::post('/payment-faker/process', [PaymentFakerController::class, 'process'])
    ->name('payment-faker.process');
```

## Référence de l'API

### PaymentFakerClient

#### `initiatePayment(array $paymentData, string $successUrl, string $cancelUrl, ?string $webhookUrl = null): array`

Initie une nouvelle transaction de paiement.

**Paramètres :**
- `$paymentData`: Tableau contenant `transaction_id`, `amount`, `currency`, `description`, `customer_name`, `customer_email`, `customer_phone_number`
- `$successUrl`: URL de redirection après paiement réussi
- `$cancelUrl`: URL de redirection si le paiement est annulé
- `$webhookUrl`: URL pour recevoir les notifications de paiement (optionnel)

**Retourne :** Tableau avec `transaction_ref`, `payment_url`, `status`, `message`

#### `approvePayment(string $transactionRef, ?bool $approve = null): array`

Approuve ou rejette manuellement un paiement. Si `$approve` est null, utilise le taux de succès configuré.

#### `cancelPayment(string $transactionRef): array`

Annule un paiement en attente.

#### `checkStatus(string $transactionRef): array`

Vérifie le statut d'une transaction de paiement.

**Retourne :** Tableau avec `status` ('PENDING', 'SUCCESS', 'FAILED', 'CANCELLED') et `message`

#### `getTransaction(string $transactionRef): array`

Obtient les détails complets d'une transaction.

#### `getAllTransactions(): array`

Obtient toutes les transactions stockées.

#### `clearTransactions(): void`

Vide toutes les transactions (utile pour les tests).

#### `triggerWebhook(string $transactionRef): bool`

Déclenche le callback webhook pour une transaction.

#### `getWebhookPayload(string $transactionRef): ?array`

Obtient les données du payload webhook pour une transaction.

## Flux de paiement

1. **Initiation** : Appelez `initiatePayment()` pour créer une nouvelle transaction
2. **Checkout** : Redirigez l'utilisateur vers l'URL retournée dans `payment_url`
3. **Approbation** : L'utilisateur approuve ou annule le paiement sur la page de checkout
4. **Webhook** : Le système envoie automatiquement une notification au webhook URL (si fourni)
5. **Vérification** : Utilisez `checkStatus()` pour vérifier le statut final

## Tests

Exécutez les tests :

```bash
composer test
```

## Notes importantes

- Cette bibliothèque est destinée uniquement aux environnements de test et développement
- Ne jamais utiliser en production avec de vrais paiements
- Les transactions sont stockées en mémoire et seront perdues au redémarrage
- Pour une persistance, implémentez votre propre système de stockage

## Licence

MIT
