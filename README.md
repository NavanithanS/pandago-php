# Pandago PHP Client Library 📦

A robust PHP client library for interacting with the Pandago API for on-demand courier delivery services. This library provides a clean, type-safe interface for all Pandago API operations with comprehensive validation and error handling.

## Table of Contents

-   [Features](#features)
-   [Requirements](#requirements)
-   [Installation](#installation)
-   [Configuration & Authentication](#configuration--authentication)
-   [Quick Start](#quick-start)
-   [Laravel Integration](#laravel-integration)
-   [API Reference](#api-reference)
    -   [Order Operations](#order-operations)
    -   [Outlet Management](#outlet-management)
-   [Error Handling](#error-handling)
-   [Supported Countries](#supported-countries)
-   [Testing](#testing)
-   [Examples](#examples)
-   [Contributing](#contributing)
-   [License](#license)
-   [Support](#support)

## Features

-   🔒 **Secure Authentication:** JWT-based authentication with RSA key pairs
-   📦 **Complete Order Management:** Create, update, cancel, and track delivery orders
-   🏪 **Outlet Management:** Create and manage delivery pickup locations
-   🌍 **Multi-Country Support:** Works across 12+ countries and regions
-   ⚡ **Real-time Tracking:** Get live courier location and order status updates
-   💰 **Fee Estimation:** Calculate delivery costs before placing orders
-   📸 **Proof of Delivery:** Retrieve delivery confirmation photos
-   ✅ **Built-in Validation:** Comprehensive parameter validation with helpful error messages
-   🛠️ **Error Handling:** Context-aware error handling with actionable suggestions
-   🚀 **Laravel Integration:** First-class Laravel support with service provider and facade
-   🧪 **Testing Support:** Mock servers and comprehensive test utilities
-   📊 **Logging:** PSR-3 compatible logging for debugging and monitoring

## Requirements

-   **PHP:** 7.1 or later
-   **Extensions:** `ext-json`
-   **Dependencies:**
    -   `GuzzleHttp/Guzzle` (^6.3|^7.0)
    -   `Firebase/php-jwt` (^5.0|^6.0)
    -   `Ramsey/uuid` (^3.8|^4.0)
    -   `Symfony/validator` (^3.4|^4.0|^5.0|^6.0)
    -   PSR-3 compatible logger

## Installation

Install via Composer:

```bash
composer require nava/pandago-php
```

## Configuration & Authentication

### 1. Generate RSA Key Pair

First, generate your RSA key pair for secure API communication:

```bash
# Generate private key
openssl genrsa -out pandago-private.pem 2048

# Generate public key from private key
openssl rsa -in pandago-private.pem -pubout > pandago-public.pem
```

Alternatively, use an online RSA generator:

-   Visit [CryptoTools RSA Generator](https://www.cryptotools.net/rsa)
-   Select 2048-bit key length
-   Click "Generate Key Pair"
-   Save the private key as `pandago-private.pem`
-   Save the public key as `pandago-public.pem`

### 2. Register with Pandago

Contact your Pandago representative and provide:

-   **Public Key:** Contents of `pandago-public.pem`
-   **Brand/Branch Details:**
    -   Name (e.g., "Store ABC")
    -   Address with coordinates
    -   Phone number
    -   Callback URL (optional, for webhooks)

You'll receive:

-   **Client ID:** `pandago:my:00000000-0000-0000-0000-000000000000`
-   **Key ID:** `00000000-0000-0000-0000-000000000001`
-   **Scope:** `pandago.api.my.*`

### 3. Basic Configuration

```php
use Nava\Pandago\Client;

$client = Client::make(
    'pandago:my:00000000-0000-0000-0000-000000000000', // Client ID
    '00000000-0000-0000-0000-000000000001',             // Key ID
    'pandago.api.my.*',                                 // Scope
    file_get_contents('/path/to/pandago-private.pem'),  // Private Key
    'my',                                               // Country
    'sandbox',                                          // Environment
    30,                                                 // Timeout (seconds)
    $logger                                             // PSR-3 Logger (optional)
);
```

## Quick Start

Here's a complete example of creating a delivery order:

```php
<?php
require_once 'vendor/autoload.php';

use Nava\Pandago\Client;
use Nava\Pandago\Models\Contact;
use Nava\Pandago\Models\Location;
use Nava\Pandago\Models\Order\CreateOrderRequest;

// Initialize client
$client = Client::make(
    'pandago:my:00000000-0000-0000-0000-000000000000',
    '00000000-0000-0000-0000-000000000001',
    'pandago.api.my.*',
    file_get_contents('/path/to/pandago-private.pem'),
    'my',
    'sandbox'
);

// Create recipient
$recipientLocation = new Location(
    '670, Era Jaya',    // Address
    7.3500280,          // Latitude
    100.4374034         // Longitude
);

$recipient = new Contact(
    'Chalit',               // Name
    '+60125918131',         // Phone Number
    $recipientLocation      // Location
    'Call upon arrival'
);

// Create order request
$orderRequest = new CreateOrderRequest(
    $recipient,                             // Recipient
    349.50,                                 // Amount
    'Woodford Reserve Kentucky Bourbon',    // Description
    'PAID'                                  // Payment Method
);

// Set sender (pickup location)
$senderLocation = new Location(
    '8, Jalan Laguna 1',    // Address
    5.3731476,              // Latitude
    100.4068053             // Longitude
);

$sender = new Contact(
    'GuangYou',                 // Name
    '+601110550716',            // Phone Number
    $senderLocation,            // Location
    'use the left side door'    // Notes
);

$orderRequest->setSender($sender);
$orderRequest->setPaymentMethod('PAID');
$orderRequest->setColdbagNeeded(true);

try {
    // Create the order
    $order = $client->orders()->create($orderRequest);

    echo "✅ Order created successfully!\n";
    echo "📦 Order ID: " . $order->getOrderId() . "\n";
    echo "📍 Status: " . $order->getStatus() . "\n";
    echo "💰 Delivery Fee: $" . $order->getDeliveryFee() . "\n";
    echo "🔗 Tracking: " . $order->getTrackingLink() . "\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
```

## Laravel Integration

### Installation & Setup

-   **Publish Configuration:**

```bash
php artisan vendor:publish --provider="Nava\Pandago\Laravel\PandagoServiceProvider"
```

-   **Environment Configuration:**

Add to your `.env` file:

```
PANDAGO_CLIENT_ID=pandago:my:00000000-0000-0000-0000-000000000000
PANDAGO_KEY_ID=00000000-0000-0000-0000-000000000001
PANDAGO_SCOPE=pandago.api.my.*
PANDAGO_PRIVATE_KEY="/path/to/pandago-private.pem"
PANDAGO_COUNTRY=my
PANDAGO_ENVIRONMENT=sandbox
PANDAGO_TIMEOUT=30
```

-   **Alternative Key Configuration:**

You can also store the private key inline or reference it in `config/pandago.php`:

```php
// config/pandago.php
'private_key' => file_get_contents(storage_path('keys/pandago-private.pem')),
// OR inline:
'private_key' => "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkq...\n-----END PRIVATE KEY-----"
```

### Usage in Laravel

#### Using the Facade

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nava\Pandago\Laravel\Facades\Pandago;
use Nava\Pandago\Models\Contact;
use Nava\Pandago\Models\Location;
use Nava\Pandago\Models\Order\CreateOrderRequest;

class DeliveryController extends Controller
{
    public function createOrder(Request $request)
    {
        $validated = $request->validate([
            'recipient_name' => 'required|string',
            'recipient_phone' => 'required|string',
            'address' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:200',
        ]);

        // Create recipient
        $recipient = new Contact(
            $validated['recipient_name'],
            $validated['recipient_phone'],
            new Location(
                $validated['address'],
                $validated['latitude'],
                $validated['longitude']
            )
        );

        // Create order
        $orderRequest = new CreateOrderRequest(
            $recipient,
            $validated['amount'],
            $validated['description']
        );

        try {
            $order = Pandago::orders()->create($orderRequest);

            return response()->json([
                'success' => true,
                'order_id' => $order->getOrderId(),
                'status' => $order->getStatus(),
                'tracking_link' => $order->getTrackingLink(),
                'delivery_fee' => $order->getDeliveryFee(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Pandago order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function trackOrder($orderId)
    {
        try {
            $order = Pandago::orders()->get($orderId);

            return response()->json([
                'order_id' => $order->getOrderId(),
                'status' => $order->getStatus(),
                'tracking_link' => $order->getTrackingLink(),
                'timeline' => $order->getTimeline()?->toArray(),
                'driver' => $order->getDriver(),
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}
```

#### Using Dependency Injection

```php
<?php

namespace App\Services;

use Nava\Pandago\Contracts\ClientInterface;
use Nava\Pandago\Models\Order\CreateOrderRequest;

class DeliveryService
{
    protected $pandago;

    public function __construct(ClientInterface $pandago)
    {
        $this->pandago = $pandago;
    }

    public function estimateDeliveryFee(CreateOrderRequest $orderRequest): float
    {
        $estimate = $this->pandago->orders()->estimateFee($orderRequest);
        return $estimate['estimated_delivery_fee'] ?? 0.0;
    }

    public function scheduleDelivery(CreateOrderRequest $orderRequest): array
    {
        $order = $this->pandago->orders()->create($orderRequest);

        return [
            'order_id' => $order->getOrderId(),
            'status' => $order->getStatus(),
            'estimated_delivery' => $order->getTimeline()?->getEstimatedDeliveryTime(),
        ];
    }
}
```

## API Reference

### Order Operations

#### Create an Order

```php
$orderRequest = new CreateOrderRequest(
    $recipient,        // Contact object
    25.50,            // Amount (float)
    'Food delivery'   // Description (string, max 200 chars)
);

// Optional configurations
$orderRequest->setClientOrderId('my-order-123');
$orderRequest->setSender($sender);  // Contact object
$orderRequest->setClientVendorId('outlet-abc-123');
$orderRequest->setPaymentMethod('CASH_ON_DELIVERY'); // or 'PAID'
$orderRequest->setColdbagNeeded(true);
$orderRequest->setCollectFromCustomer(30.00); // For cash collection
$orderRequest->setPreorderedFor(strtotime('+2 hours')); // Schedule delivery

// Delivery tasks
$orderRequest->setDeliveryTasks([
    'age_validation_required' => true  // For age-restricted items
]);

$order = $client->orders()->create($orderRequest);
```

#### Get Order Details

```php
$order = $client->orders()->get($orderId);

// Access order properties
echo "Order ID: " . $order->getOrderId() . "\n";
echo "Client Order ID: " . $order->getClientOrderId() . "\n";
echo "Status: " . $order->getStatus() . "\n";
echo "Amount: RM" . $order->getAmount() . "\n";
echo "Delivery Fee: RM" . $order->getDeliveryFee() . "\n";
echo "Distance: " . $order->getDistance() . " meters\n";
echo "Payment Method: " . $order->getPaymentMethod() . "\n";
echo "Created: " . date('Y-m-d H:i:s', $order->getCreatedAt()) . "\n";
echo "Updated: " . date('Y-m-d H:i:s', $order->getUpdatedAt()) . "\n";

// Timeline information
$timeline = $order->getTimeline();
if ($timeline) {
    echo "Est. Pickup: " . $timeline->getEstimatedPickupTime() . "\n";
    echo "Est. Delivery: " . $timeline->getEstimatedDeliveryTime() . "\n";
}

// Driver information
$driver = $order->getDriver();
if ($driver && !empty($driver['name'])) {
    echo "Driver: " . $driver['name'] . " (" . $driver['phone_number'] . ")\n";
}
```

#### Update an Order

```php
use Nava\Pandago\Models\Order\UpdateOrderRequest;
use Nava\Pandago\Models\Location;

$updateRequest = new UpdateOrderRequest();

// Update payment method
$updateRequest->setPaymentMethod('PAID');
$updateRequest->setAmount(0); // Set to 0 when changing to PAID

// Update delivery location
$newLocation = new Location(
    '456 New Address',
    1.2900,
    103.8500
);
$updateRequest->setLocation($newLocation, 'Updated delivery instructions');

// Update description
$updateRequest->setDescription('Updated order description');

$updatedOrder = $client->orders()->update($orderId, $updateRequest);
```

#### Cancel an Order

```php
use Nava\Pandago\Models\Order\CancelOrderRequest;

$cancelRequest = new CancelOrderRequest('MISTAKE_ERROR');
// Available reasons: DELIVERY_ETA_TOO_LONG, MISTAKE_ERROR, REASON_UNKNOWN

$success = $client->orders()->cancel($orderId, $cancelRequest);

if ($success) {
    echo "Order cancelled successfully\n";

    // Check cancellation details
    $cancelledOrder = $client->orders()->get($orderId);
    $cancellation = $cancelledOrder->getCancellation();

    if ($cancellation) {
        echo "Cancellation reason: " . $cancellation['reason'] . "\n";
        echo "Cancelled by: " . $cancellation['source'] . "\n";
    }
}
```

#### Track Courier Location

```php
$coordinates = $client->orders()->getCoordinates($orderId);

echo "Courier Location:\n";
echo "Latitude: " . $coordinates->getLatitude() . "\n";
echo "Longitude: " . $coordinates->getLongitude() . "\n";
echo "Last Updated: " . date('Y-m-d H:i:s', $coordinates->getUpdatedAt()) . "\n";

if ($coordinates->getClientOrderId()) {
    echo "Client Order ID: " . $coordinates->getClientOrderId() . "\n";
}
```

#### Get Delivery Proofs

```php
try {
    // Get proof of delivery (Base64 encoded image)
    $deliveryProof = $client->orders()->getProofOfDelivery($orderId);
    file_put_contents('proof_of_delivery.jpg', base64_decode($deliveryProof));
    echo "Proof of delivery saved\n";

    // Get proof of pickup
    $pickupProof = $client->orders()->getProofOfPickup($orderId);
    file_put_contents('proof_of_pickup.jpg', base64_decode($pickupProof));
    echo "Proof of pickup saved\n";

    // Get proof of return (if order was returned)
    $returnProof = $client->orders()->getProofOfReturn($orderId);
    file_put_contents('proof_of_return.jpg', base64_decode($returnProof));
    echo "Proof of return saved\n";

} catch (Exception $e) {
    echo "Proof not available: " . $e->getMessage() . "\n";
}
```

#### Estimate Delivery Fee and Time

```php
// Create order request for estimation (same as creating order)
$estimateRequest = new CreateOrderRequest($recipient, 25.50, 'Test order');
$estimateRequest->setSender($sender);

// Estimate delivery fee
$feeEstimate = $client->orders()->estimateFee($estimateRequest);
echo "Estimated Delivery Fee: $" . $feeEstimate['estimated_delivery_fee'] . "\n";

// Estimate delivery time
$timeEstimate = $client->orders()->estimateTime($estimateRequest);
echo "Estimated Pickup Time: " . $timeEstimate['estimated_pickup_time'] . "\n";
echo "Estimated Delivery Time: " . $timeEstimate['estimated_delivery_time'] . "\n";
```

### Outlet Management

#### Create or Update an Outlet

```php
use Nava\Pandago\Models\Outlet\Outlet;

$outlet = new Outlet([
    'name' => 'Trilobyte',
    'address' => '1st Floor, No 8',
    'latitude' => 5.3731476,
    'longitude' => 100.4068053,
    'city' => 'Penang',
    'phone_number' => '+601110550716',
    'currency' => 'MYR',
    'locale' => 'en-MY',
    'description' => 'Authentic Malaysian cuisine since 1988'
]);

// Set additional outlet details (all optional)
$outlet->setStreet('Jalan Laguna 1');
$outlet->setStreetNumber('1');
$outlet->setPostalCode('13700');
$outlet->setRiderInstructions('Masuk melalui pintu belakang, parkir di lot A');
$outlet->setHalal(true);

// User management
$outlet->addUsers(['manager@restaurant.com', 'staff@restaurant.com']);

$clientVendorId = 'outlet-' . uniqid();
$createdOutlet = $client->outlets()->createOrUpdate($clientVendorId, $outlet);

echo "Outlet created with ID: " . $createdOutlet->getClientVendorId() . "\n";
```

#### Get Outlet Details

```php
$outlet = $client->outlets()->get($clientVendorId);

echo "Outlet Details:\n";
echo "Name: " . $outlet->getName() . "\n";
echo "Address: " . $outlet->getAddress() . "\n";
echo "City: " . $outlet->getCity() . "\n";
echo "Phone: " . $outlet->getPhoneNumber() . "\n";
echo "Currency: " . $outlet->getCurrency() . "\n";
echo "Locale: " . $outlet->getLocale() . "\n";
echo "Halal Certified: " . ($outlet->isHalal() ? 'Yes' : 'No') . "\n";

if ($outlet->getRiderInstructions()) {
    echo "Rider Instructions: " . $outlet->getRiderInstructions() . "\n";
}

$users = $outlet->getUsers();
if ($users) {
    echo "Authorized Users: " . implode(', ', $users) . "\n";
}
```

#### Get All Outlets

```php
$outlets = $client->outlets()->getAll();

echo "Total Outlets: " . count($outlets) . "\n";

foreach ($outlets as $outlet) {
    echo "- " . $outlet->getName() . " (" . $outlet->getClientVendorId() . ")\n";
    echo "  Address: " . $outlet->getAddress() . "\n";
    echo "  Phone: " . $outlet->getPhoneNumber() . "\n";
    echo "---\n";
}
```

## Error Handling

The library provides comprehensive error handling with context-aware error messages:

```php
use Nava\Pandago\Exceptions\ValidationException;
use Nava\Pandago\Exceptions\AuthenticationException;
use Nava\Pandago\Exceptions\RequestException;
use Nava\Pandago\Exceptions\PandagoException;
use Nava\Pandago\Util\ErrorHandler;

try {
    $order = $client->orders()->create($orderRequest);

} catch (ValidationException $e) {
    // Handle validation errors
    echo "❌ Validation Error: " . $e->getMessage() . "\n";

    $errors = $e->getErrors();
    foreach ($errors as $field => $message) {
        echo "  - {$field}: {$message}\n";
    }

} catch (AuthenticationException $e) {
    // Handle authentication errors
    echo "🔐 Authentication Error: " . $e->getMessage() . "\n";
    echo "💡 Check your client ID, key ID, and private key configuration\n";

} catch (RequestException $e) {
    // Handle API request errors with detailed context
    echo "🌐 API Error: " . ErrorHandler::getDetailedErrorMessage($e) . "\n";

    // Access specific error details
    echo "Status Code: " . $e->getCode() . "\n";
    echo "Method: " . $e->getMethod() . " " . $e->getEndpoint() . "\n";

    // Get user-friendly suggestions
    echo "Suggestion: " . $e->getFriendlyMessage() . "\n";

    // Check for specific error types
    if ($e->getCode() === 404 && strpos($e->getMessage(), 'Outlet not found') !== false) {
        echo "💡 Tip: Verify that the client vendor ID exists and you have permission to access it\n";
    }

} catch (PandagoException $e) {
    // Handle any other Pandago-specific errors
    echo "⚠️ Pandago Error: " . $e->getMessage() . "\n";

} catch (Exception $e) {
    // Handle unexpected errors
    echo "💥 Unexpected Error: " . $e->getMessage() . "\n";
}
```

### Common Error Scenarios and Solutions

| Error Code | Scenario               | Solution                                                              |
| ---------- | ---------------------- | --------------------------------------------------------------------- |
| 401        | Authentication failed  | Check client ID, key ID, and private key. Ensure token hasn't expired |
| 404        | Outlet/Order not found | Verify the ID exists and you have access permissions                  |
| 409        | Order not cancellable  | Order has progressed beyond cancellation point                        |
| 422        | Validation failed      | Check request parameters format and required fields                   |
| 429        | Rate limit exceeded    | Reduce request frequency and implement backoff strategy               |
| 500        | Server error           | Pandago API issue - retry later or contact support                    |

## Supported Countries

The library supports delivery services in the following countries:

| Country Code | Country Name | Production API                   |
| ------------ | ------------ | -------------------------------- |
| sg           | Singapore    | pandago-api-apse.deliveryhero.io |
| hk           | Hong Kong    | pandago-api-apse.deliveryhero.io |
| my           | Malaysia     | pandago-api-apse.deliveryhero.io |
| th           | Thailand     | pandago-api-apse.deliveryhero.io |
| ph           | Philippines  | pandago-api-apse.deliveryhero.io |
| tw           | Taiwan       | pandago-api-apse.deliveryhero.io |
| pk           | Pakistan     | pandago-api-apso.deliveryhero.io |
| jo           | Jordan       | pandago-api-apse.deliveryhero.io |
| fi           | Finland      | pandago-api-apse.deliveryhero.io |
| kw           | Kuwait       | pandago-api-apse.deliveryhero.io |
| no           | Norway       | pandago-api-apse.deliveryhero.io |
| se           | Sweden       | pandago-api-apse.deliveryhero.io |

**Sandbox Environment:** All countries use `pandago-api-sandbox.deliveryhero.io`

## Testing

### Running Tests

```bash
# Run all tests
composer test

# Run specific test suites
./vendor/bin/phpunit tests/Unit
./vendor/bin/phpunit tests/Integration

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage
```

### Code Quality

```bash
# Run static analysis
composer analyse

# Check code style
composer cs

# Fix code style
composer cs-fix
```

### Mock Callback Server

For testing webhooks locally:

```php
use Nava\Pandago\Tests\Util\MockCallbackServer;

$mockServer = new MockCallbackServer(8000, '/pandago-callback');
$mockServer->start();

// Your test code here...
// Create orders that will trigger callbacks

// Process callbacks
$mockServer->processCallbacks();

// Check received callbacks
$callbacks = $mockServer->getReceivedCallbacks();
$orderCallbacks = $mockServer->getCallbacksForOrder($orderId);

$mockServer->stop();
```

### Integration Testing

Create a test configuration file:

```php
// tests/config.php
return [
    'client_id'   => 'pandago:my:00000000-0000-0000-0000-000000000000',
    'key_id'      => '00000000-0000-0000-0000-000000000001',
    'scope'       => 'pandago.api.my.*',
    'private_key' => file_get_contents('path/to/private-key.pem'),
    'country'     => 'my',
    'environment' => 'sandbox',
];
```

## Examples

### Complete Order Lifecycle

```php
<?php
require_once 'vendor/autoload.php';

use Nava\Pandago\Client;
use Nava\Pandago\Models\Contact;
use Nava\Pandago\Models\Location;
use Nava\Pandago\Models\Order\CreateOrderRequest;
use Nava\Pandago\Models\Order\CancelOrderRequest;

// Initialize client
$client = Client::fromArray([
    'client_id'   => getenv('PANDAGO_CLIENT_ID'),
    'key_id'      => getenv('PANDAGO_KEY_ID'),
    'scope'       => getenv('PANDAGO_SCOPE'),
    'private_key' => file_get_contents(getenv('PANDAGO_PRIVATE_KEY_PATH')),
    'country'     => 'my',
    'environment' => 'sandbox',
]);

try {
    // 1. Estimate delivery fee first
    echo "1️⃣ Estimating delivery fee...\n";

    $recipient = new Contact(
        'Chalit',
        '+60125918131',
        new Location('670, Era Jaya', 7.3500280, 100.4374034)
    );

    $estimateRequest = new CreateOrderRequest($recipient, 35.00, 'Gourmet burger meal');
    $estimateRequest->setSender(new Contact(
        'GuangYou',
        '+601110550716',
        new Location('8, Jalan Laguna 1', 5.3731476, 100.4068053)
    ));

    $feeEstimate = $client->orders()->estimateFee($estimateRequest);
    echo "💰 Estimated fee: $" . $feeEstimate['estimated_delivery_fee'] . "\n";

    // 2. Create the actual order
    echo "\n2️⃣ Creating order...\n";
    $estimateRequest->setClientOrderId('order-' . time());
    $estimateRequest->setPaymentMethod('CASH_ON_DELIVERY');
    $estimateRequest->setColdbagNeeded(true);

    $order = $client->orders()->create($estimateRequest);
    echo "✅ Order created: " . $order->getOrderId() . "\n";
    echo "📍 Status: " . $order->getStatus() . "\n";

    // 3. Track order status
    echo "\n3️⃣ Tracking order...\n";
    $orderId = $order->getOrderId();

    $attempts = 0;
    do {
        sleep(10); // Wait 10 seconds
        $currentOrder = $client->orders()->get($orderId);
        $status = $currentOrder->getStatus();
        echo "📊 Current status: {$status}\n";

        // If courier is assigned, get location
        if (in_array($status, ['COURIER_ACCEPTED_DELIVERY', 'PICKED_UP', 'NEAR_CUSTOMER'])) {
            try {
                $coordinates = $client->orders()->getCoordinates($orderId);
                echo "📍 Courier location: {$coordinates->getLatitude()}, {$coordinates->getLongitude()}\n";
            } catch (Exception $e) {
                echo "📍 Courier location not available yet\n";
            }
        }

        $attempts++;
    } while (!in_array($status, ['DELIVERED', 'CANCELLED']) && $attempts < 10);

    // 4. Handle final status
    if ($status === 'DELIVERED') {
        echo "\n4️⃣ Order delivered! Getting proof...\n";
        try {
            $proofOfDelivery = $client->orders()->getProofOfDelivery($orderId);
            file_put_contents("delivery_proof_{$orderId}.jpg", base64_decode($proofOfDelivery));
            echo "📸 Proof of delivery saved\n";
        } catch (Exception $e) {
            echo "📸 Proof of delivery not available: " . $e->getMessage() . "\n";
        }
    } else {
        // 5. Cancel order if needed (only if not picked up)
        echo "\n5️⃣ Cancelling order...\n";
        try {
            $cancelRequest = new CancelOrderRequest('MISTAKE_ERROR');
            $client->orders()->cancel($orderId, $cancelRequest);
            echo "❌ Order cancelled successfully\n";
        } catch (Exception $e) {
            echo "❌ Could not cancel order: " . $e->getMessage() . "\n";
        }
    }

} catch (Exception $e) {
    echo "💥 Error: " . $e->getMessage() . "\n";
}
```

### Webhook Handler

```php
<?php
// webhook.php - Handle Pandago callbacks

require_once 'vendor/autoload.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);

if (!$payload) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Log the callback
error_log('Pandago callback: ' . json_encode($payload));

$orderId = $payload['order_id'] ?? null;
$status = $payload['status'] ?? null;
$clientOrderId = $payload['client_order_id'] ?? null;

if (!$orderId || !$status) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // Update your database
    updateOrderStatus($orderId, $status, $payload);

    // Handle specific statuses
    switch ($status) {
        case 'COURIER_ACCEPTED_DELIVERY':
            notifyCustomer($orderId, 'Your order has been picked up by a courier!');
            break;

        case 'NEAR_CUSTOMER':
            notifyCustomer($orderId, 'Your courier is nearby!');
            break;

        case 'DELIVERED':
            notifyCustomer($orderId, 'Your order has been delivered!');
            processDeliveredOrder($orderId, $payload);
            break;

        case 'CANCELLED':
            $reason = $payload['cancellation']['reason'] ?? 'Unknown';
            notifyCustomer($orderId, "Your order was cancelled. Reason: {$reason}");
            processRefund($orderId);
            break;
    }

    // Return success
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log('Webhook processing error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

function updateOrderStatus($orderId, $status, $payload) {
    // Your database update logic
    // UPDATE orders SET status = ?, updated_at = NOW() WHERE pandago_order_id = ?
}

function notifyCustomer($orderId, $message) {
    // Send SMS, email, or push notification
}

function processDeliveredOrder($orderId, $payload) {
    // Mark as complete, update inventory, etc.
}

function processRefund($orderId) {
    // Process refund if payment was collected
}
```

## Contributing

We welcome contributions! Please follow these guidelines:

### Development Setup

-   Fork and clone the repository:

```bash
git clone https://github.com/NavanithanS/pandago-php.git
cd pandago-php
```

-   Install dependencies:

```bash
composer install
```

-   Set up testing environment:

```bash
cp tests/config.example.php tests/config.php
# Edit tests/config.php with your test credentials
```

-   Run tests:

```bash
composer test
composer analyse
composer cs
```

### Contribution Process

-   Create a feature branch:

```bash
git checkout -b feature/awesome-feature
```

-   Make your changes following PSR-12 coding standards
-   Add tests for new functionality
-   Update documentation if needed
-   Run quality checks:

```bash
composer test
composer analyse
composer cs-fix
```

-   Commit your changes:

```bash
git commit -m 'Add awesome feature'
```

-   Push and create a Pull Request:

```bash
git push origin feature/awesome-feature
```

### Guidelines

-   Follow PSR-12 coding standards
-   Write tests for all new functionality
-   Update documentation for any API changes
-   Keep backward compatibility when possible
-   Use meaningful commit messages
-   Add type hints and proper PHPDoc comments

## License

This library is licensed under the MIT License. See the [LICENSE](#license) section for details.

### MIT License

```
Copyright (c) 2025 Nava

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
```

## Support

### Getting Help

-   📧 **Email:** gua@navins.biz
-   🐛 **Issues:** [GitHub Issues](https://github.com/NavanithanS/pandago-php/issues)
-   📖 **Documentation:** [API Documentation](#api-reference)

### Security Issues

If you discover any security vulnerabilities, please email `gua@navins.biz` instead of using the issue tracker.

This library is not officially affiliated with Delivery Hero or Pandago. It's an independent client library created to facilitate integration with the Pandago API.
