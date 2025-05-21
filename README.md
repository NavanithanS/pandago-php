# Pandago PHP Client Library 📦

A robust PHP client library for interacting with the pandago API for on-demand courier delivery services. This library provides a clean, type-safe interface for all pandago API operations with comprehensive validation and error handling.

## Features

-   🔒 Secure JWT-based authentication and token management
-   📦 Complete order operations support:
    -   Order creation and management
    -   Real-time tracking and status updates
    -   Fee and delivery time estimation
    -   Order cancellation
    -   Proof of delivery/pickup retrieval
-   🏪 Full outlet management capabilities
-   ✅ Built-in validation for all request parameters
-   🛠️ Comprehensive error handling with context-aware suggestions
-   🚀 Laravel integration via service provider and facade
-   🧪 Extensive test coverage

## Requirements

-   PHP 7.1 or later
-   ext-json
-   GuzzleHttp/Guzzle (^6.3|^7.0)
-   Firebase/php-jwt (^5.0|^6.0)
-   Ramsey/uuid (^3.8|^4.0)
-   Symfony/validator (^3.4|^4.0|^5.0|^6.0)
-   PSR-3 compatible logger

## Installation

Install via Composer:

```bash
composer require nava/pandago-php
```

## Quick Start

```php
use Nava\Pandago\Client;
use Nava\Pandago\Models\Contact;
use Nava\Pandago\Models\Location;
use Nava\Pandago\Models\Order\CreateOrderRequest;

// Create a client
$client = Client::make(
    'pandago:my:00000000-0000-0000-0000-000000000000', // ClientID
    '00000000-0000-0000-0000-000000000001',             // KeyID
    'pandago.api.my.*',                                 // Scope
    file_get_contents('path/to/client.pem'),            // Private Key
    'my',                                               // Country (default: my)
    'sandbox'                                           // Environment (default: sandbox)
);

// Create a delivery order
$location = new Location(
    '670, Era Jaya',    // Address
    7.3500280,          // Latitude
    100.4374034         // Longitude
);

$recipient = new Contact(
    'Chalit',             // Name
    '+60125918131',     // Phone Number
    $location           // Location
);

$request = new CreateOrderRequest(
    $recipient,                             // Recipient
    349.50,                                  // Amount
    'Woodford Reserve Kentucky Bourbon',    // Description
    'PAID'                                  // Payment Method
);

// Set the sender
$senderLocation = new Location(
    '8, Jalan Laguna 1',  // Address
    5.3731476,              // Latitude
    100.4068053             // Longitude
);

$sender = new Contact(
    'GuangYou',              // Name
    '+601110550716',          // Phone Number
    $senderLocation,        // Location
    'use the left side door' // Notes
);

$request->setSender($sender);

// Set additional options if needed
$request->setPaymentMethod('PAID');
$request->setColdbagNeeded(true);

// Create the order
try {
    $order = $client->orders()->create($request);

    echo "Order created successfully!\n";
    echo "Order ID: " . $order->getOrderId() . "\n";
    echo "Status: " . $order->getStatus() . "\n";
    echo "Tracking Link: " . $order->getTrackingLink() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

## Laravel Integration

This package includes Laravel integration through a service provider and facade.

### Setup

First, publish the configuration file:

```bash
php artisan vendor:publish --provider="Nava\Pandago\Laravel\PandagoServiceProvider"
```

Then, add your Pandago API credentials to your `.env` file:

```env
PANDAGO_CLIENT_ID=pandago:my:00000000-0000-0000-0000-000000000000
PANDAGO_KEY_ID=00000000-0000-0000-0000-000000000001
PANDAGO_SCOPE=pandago.api.my.*
PANDAGO_PRIVATE_KEY="/path/to/your/private-key.pem"
# OR use inline private key:
# PANDAGO_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----
# MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSj...
# -----END PRIVATE KEY-----"
PANDAGO_COUNTRY=my
PANDAGO_ENVIRONMENT=sandbox
```

Alternatively, you can reference a private key file in the `config/pandago.php` file:

```php
'private_key' => file_get_contents(storage_path('keys/pandago.pem')),
```

### Usage

You can use the Facade or dependency injection:

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
        // Validate the incoming request
        $validated = $request->validate([
            'recipient_name' => 'required|string',
            'recipient_phone' => 'required|string',
            'address' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'amount' => 'required|numeric',
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

        // Create order request
        $orderRequest = new CreateOrderRequest(
            $recipient,
            $validated['amount'],
            $validated['description']
        );

        // Set client vendor ID for the outlet
        $orderRequest->setClientVendorId(config('services.pandago.outlet_id'));

        try {
            // Create the order
            $order = Pandago::orders()->create($orderRequest);

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Delivery order created successfully',
                'order_id' => $order->getOrderId(),
                'status' => $order->getStatus(),
                'tracking_link' => $order->getTrackingLink(),
            ]);
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Pandago order creation failed: ' . $e->getMessage());

            // Return error response
            return response()->json([
                'success' => false,
                'message' => 'Failed to create delivery order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
```

## API Reference

### Order Operations

#### Create an Order

```php
// Create an order
$order = $client->orders()->create($createOrderRequest);
```

#### Get Order Details

```php
// Get order details
$order = $client->orders()->get($orderId);

// Access order properties
echo "Order ID: " . $order->getOrderId() . "\n";
echo "Client Order ID: " . $order->getClientOrderId() . "\n";
echo "Status: " . $order->getStatus() . "\n";
echo "Amount: RM" . $order->getAmount() . "\n";
echo "Delivery Fee: RM" . $order->getDeliveryFee() . "\n";
echo "Created At: " . date('Y-m-d H:i:s', $order->getCreatedAt()) . "\n";
```

#### Update an Order

```php
// Create update request
$updateRequest = new UpdateOrderRequest();

// Set fields to update
$updateRequest->setAmount(42.50);
$updateRequest->setDescription('Updated order: ');

// Update the order
$updatedOrder = $client->orders()->update($orderId, $updateRequest);
```

#### Cancel an Order

```php
// Create cancel request with reason
$cancelRequest = new CancelOrderRequest('MISTAKE_ERROR');
// Available reasons: DELIVERY_ETA_TOO_LONG, MISTAKE_ERROR, REASON_UNKNOWN

// Cancel the order
$success = $client->orders()->cancel($orderId, $cancelRequest);

if ($success) {
    echo "Order cancelled successfully\n";
}
```

#### Track Courier Location

```php
// Get real-time courier coordinates
$coordinates = $client->orders()->getCoordinates($orderId);
echo "Courier is at: " . $coordinates->getLatitude() . ", " . $coordinates->getLongitude() . "\n";
echo "Last updated: " . date('Y-m-d H:i:s', $coordinates->getUpdatedAt()) . "\n";
```

#### Get Delivery Proofs

```php
// Get proof of delivery (Base64 encoded image)
$deliveryProof = $client->orders()->getProofOfDelivery($orderId);

// Get proof of pickup (Base64 encoded image)
$pickupProof = $client->orders()->getProofOfPickup($orderId);

// Get proof of return (Base64 encoded image) - if order was returned
$returnProof = $client->orders()->getProofOfReturn($orderId);

// Example: Save proof of delivery as image
file_put_contents('delivery-proof.jpg', base64_decode($deliveryProof));
```

#### Estimate Delivery Fee and Time

```php
// Estimate delivery fee before creating order
$feeEstimate = $client->orders()->estimateFee($createOrderRequest);
echo "Estimated Delivery Fee: RM" . $feeEstimate['estimated_delivery_fee'] . "\n";

// Estimate delivery time
$timeEstimate = $client->orders()->estimateTime($createOrderRequest);
echo "Estimated Pickup Time: " . $timeEstimate['estimated_pickup_time'] . "\n";
echo "Estimated Delivery Time: " . $timeEstimate['estimated_delivery_time'] . "\n";
```

### Outlet Management

#### Get Outlet Details

```php
// Retrieve outlet by client vendor ID
$outlet = $client->outlets()->get($clientVendorId);

echo "Outlet Name: " . $outlet->getName() . "\n";
echo "Address: " . $outlet->getAddress() . "\n";
echo "City: " . $outlet->getCity() . "\n";
echo "Phone: " . $outlet->getPhoneNumber() . "\n";
```

#### Create or Update an Outlet

```php
// Create outlet request
$request = new CreateOutletRequest(
    'Trilobyte',                                 // Name
    '1st Floor, No 8',                           // Address
    5.3731476,                                   // Latitude
    100.4068053,                                 // Longitude
    'Kuala Lumpur',                              // City
    '+601110550716',                             // Phone number
    'MYR',                                       // Currency
    'ms-MY',                                     // Locale
    'Authentic Malaysian cuisine since 1988'     // Description (optional)
);

// Set additional outlet details (all optional)
$request->setStreet('Jalan Laguna 1');
$request->setStreetNumber('1');
$request->setPostalCode('13700');
$request->setRiderInstructions('Masuk melalui pintu belakang, parkir di lot A');
$request->setHalal(true);

// Add users who can manage this outlet
$request->setAddUsers(['user1@example.com', 'user2@example.com']);

// Create or update the outlet
$outlet = $client->outlets()->createOrUpdate($clientVendorId, $request);
```

#### Get All Outlets

```php
// Retrieve all outlets for your account
$outlets = $client->outlets()->getAll();

foreach ($outlets as $outlet) {
    echo "Outlet ID: " . $outlet->getClientVendorId() . "\n";
    echo "Name: " . $outlet->getName() . "\n";
    echo "Address: " . $outlet->getAddress() . "\n";
    echo "-----------------------------------\n";
}
```

## Error Handling

The library provides comprehensive error handling with context-aware error messages and helpful suggestions.

```php
use Nava\Pandago\Exceptions\ValidationException;
use Nava\Pandago\Exceptions\AuthenticationException;
use Nava\Pandago\Exceptions\RequestException;
use Nava\Pandago\Exceptions\PandagoException;
use Nava\Pandago\Util\ErrorHandler;

try {
    $order = $client->orders()->create($request);
} catch (ValidationException $e) {
    // Handle validation errors (e.g., invalid parameters)
    echo "Validation error: " . $e->getMessage() . "\n";

    $errors = $e->getErrors();
    foreach ($errors as $field => $message) {
        echo "- $field: $message\n";
    }
} catch (AuthenticationException $e) {
    // Handle authentication errors (e.g., invalid credentials, expired token)
    echo "Authentication error: " . $e->getMessage() . "\n";
    echo "Please check your client ID, key ID, and private key.\n";
} catch (RequestException $e) {
    // Handle API request errors (e.g., 400, 404, 500 responses)

    // Get a detailed error message with contextual information
    echo ErrorHandler::getDetailedErrorMessage($e) . "\n";

    // Access error details individually
    echo "Status code: " . $e->getCode() . "\n";
    echo "Error message: " . $e->getMessage() . "\n";
    echo "Endpoint: " . $e->getMethod() . " " . $e->getEndpoint() . "\n";

    // Use the friendly message which includes helpful suggestions
    echo "Friendly message: " . $e->getFriendlyMessage() . "\n";
} catch (PandagoException $e) {
    // Handle any other pandago-specific errors
    echo "Pandago error: " . $e->getMessage() . "\n";
} catch (\Exception $e) {
    // Handle any other unexpected errors
    echo "Unexpected error: " . $e->getMessage() . "\n";
}
```

The error handler provides specific suggestions based on error type:

| Error Type           | Example Suggestion                                                                    |
| -------------------- | ------------------------------------------------------------------------------------- |
| Authentication (401) | "Your authentication token may have expired or is invalid. Try refreshing the token." |
| Not Found (404)      | "The requested resource was not found. Verify the ID or path is correct."             |
| Validation (422)     | "The request data is likely invalid. Check your parameters."                          |
| Rate Limiting (429)  | "You've exceeded the rate limit. Please reduce the frequency of your requests."       |
| Server Error (500)   | "The pandago API is experiencing issues. Please try again later."                     |

## Configuration Options

### Client Initialization

You can create a client using the factory method with various options:

```php
// Using the make method
$client = Client::make(
    'pandago:my:00000000-0000-0000-0000-000000000000', // Client ID
    '00000000-0000-0000-0000-000000000001',            // Key ID
    'pandago.api.my.*',                                // Scope
    file_get_contents('path/to/private-key.pem'),      // Private Key
    'my',                                              // Country code
    'sandbox',                                         // Environment
    30,                                                // Timeout in seconds
    $logger                                            // PSR-3 Logger instance (optional)
);

// Alternatively, using array-based configuration
$client = Client::fromArray([
    'client_id'   => 'pandago:my:00000000-0000-0000-0000-000000000000',
    'key_id'      => '00000000-0000-0000-0000-000000000001',
    'scope'       => 'pandago.api.my.*',
    'private_key' => file_get_contents('path/to/private-key.pem'),
    'country'     => 'my',
    'environment' => 'sandbox',
    'timeout'     => 30
], $logger);
```

### Supported Countries

The library supports multiple countries:

| Country Code | Country Name |
| ------------ | ------------ |
| sg           | Singapore    |
| hk           | Hong Kong    |
| my           | Malaysia     |
| th           | Thailand     |
| ph           | Philippines  |
| tw           | Taiwan       |
| pk           | Pakistan     |
| jo           | Jordan       |
| fi           | Finland      |
| kw           | Kuwait       |
| no           | Norway       |
| se           | Sweden       |

## Testing

Run the test suite:

```bash
composer test
```

Run static analysis:

```bash
composer analyse
```

Format code:

```bash
composer cs-fix
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/awesome-feature`)
3. Commit your changes (`git commit -m 'Add awesome feature'`)
4. Push to the branch (`git push origin feature/awesome-feature`)
5. Create a Pull Request

## Security

If you discover any security vulnerabilities, please email gua@navins.biz instead of using the issue tracker.

## License

This library is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Credits

[All Contributors](../../contributors)
