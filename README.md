# Pandago PHP Client Library 📦

A robust PHP client library for interacting with the pandago API for on-demand courier delivery services. This library provides a clean, type-safe interface for all pandago API operations with comprehensive validation and error handling.

## Features

-   🔒 Secure JWT-based authentication and token management
-   📦 Complete order operations support:
    -   Order creation and submission
    -   Order tracking and status updates
    -   Order cancellation and modification
-   🏪 Full outlet management capabilities
-   ✅ Built-in validation for all entity types
-   🛠️ Comprehensive error handling with specific exception types
-   📦 Laravel integration via service provider and facade
-   🧪 Detailed test coverage

## Requirements

-   PHP 7.1 or higher
-   ext-json
-   Guzzle HTTP Client
-   Firebase JWT
-   Ramsey UUID

## Installation

Install via Composer:

```bash
composer require Nava/pandago-php
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

// Create the order
try {
    $order = $client->orders()->create($request);
    echo "Order created with ID: " . $order->getOrderId() . "\n";
    echo "Status: " . $order->getStatus() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

## Laravel Integration

This package provides a Laravel service provider to make integration easy.

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
PANDAGO_PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----
...
-----END RSA PRIVATE KEY-----"
PANDAGO_COUNTRY=my
PANDAGO_ENVIRONMENT=sandbox
```

Alternatively, you can store your private key in a file and reference it in the `config/pandago.php` file:

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

class OrderController extends Controller
{
    public function create(Request $request)
    {
        $location = new Location(
            $request->input('address'),
            $request->input('latitude'),
            $request->input('longitude')
        );

        $recipient = new Contact(
            $request->input('name'),
            $request->input('phone'),
            $location
        );

        $orderRequest = new CreateOrderRequest(
            $recipient,
            $request->input('amount'),
            $request->input('description')
        );

        // Set sender or client vendor ID
        $orderRequest->setClientVendorId($request->input('outlet_id'));

        try {
            $order = Pandago::orders()->create($orderRequest);

            return response()->json([
                'order_id' => $order->getOrderId(),
                'status' => $order->getStatus(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
```

## Advanced Usage

### Orders

#### Create an Order

```php
$order = $client->orders()->create($createOrderRequest);
```

#### Get an Order

```php
$order = $client->orders()->get($orderId);
```

#### Update an Order

```php
$updateRequest = new UpdateOrderRequest();
$updateRequest->setAmount(25.0);
$updateRequest->setDescription('Updated description');

$order = $client->orders()->update($orderId, $updateRequest);
```

#### Cancel an Order

```php
$cancelRequest = new CancelOrderRequest('MISTAKE_ERROR');
$success = $client->orders()->cancel($orderId, $cancelRequest);
```

#### Get Courier Coordinates

```php
$coordinates = $client->orders()->getCoordinates($orderId);
echo "Latitude: " . $coordinates->getLatitude() . "\n";
echo "Longitude: " . $coordinates->getLongitude() . "\n";
```

#### Proof of Delivery and Pickup

```php
// Get proof of delivery
$base64Image = $client->orders()->getProofOfDelivery($orderId);

// Get proof of pickup
$base64Image = $client->orders()->getProofOfPickup($orderId);

// Get proof of return
$base64Image = $client->orders()->getProofOfReturn($orderId);
```

#### Estimate Delivery Fee and Time

```php
// Estimate delivery fee
$fee = $client->orders()->estimateFee($createOrderRequest);
echo "Estimated Fee: " . $fee['estimated_delivery_fee'] . "\n";

// Estimate delivery time
$time = $client->orders()->estimateTime($createOrderRequest);
echo "Estimated Pickup: " . $time['estimated_pickup_time'] . "\n";
echo "Estimated Delivery: " . $time['estimated_delivery_time'] . "\n";
```

### Outlets

#### Get an Outlet

```php
$outlet = $client->outlets()->get($clientVendorId);
```

#### Create or Update an Outlet

```php
$outlet = new Outlet();
$outlet->setName('Trilobyte');
$outlet->setAddress('1st Floor, No 8, Jalan Laguna 1');
$outlet->setLatitude(5.3731476);
$outlet->setLongitude(100.4068053);
$outlet->setCity('Prai');
$outlet->setPhoneNumber('+601110550716');
$outlet->setCurrency('MYR');
$outlet->setLocale('en-MY');
$outlet->setDescription('My store description');


$createdOutlet = $client->outlets()->createOrUpdate($clientVendorId, $outlet);
```

#### Get All Outlets

```php
$outlets = $client->outlets()->all();
foreach ($outlets as $outlet) {
    echo $outlet->getName() . "\n";
}
```

### Error Handling

The library provides enhanced error handling with detailed, context-rich error messages and helpful suggestions.

#### Basic Error Handling

```php
use Nava\Pandago\Exceptions\ValidationException;
use Nava\Pandago\Exceptions\AuthenticationException;
use Nava\Pandago\Exceptions\RequestException;
use Nava\Pandago\Exceptions\PandagoException;
use Nava\Pandago\Util\ErrorHandler;

try {
    $result = $client->orders()->create($request);
} catch (ValidationException $e) {
    // Handle validation errors
    $errors = $e->getErrors();
    foreach ($errors as $field => $message) {
        echo "Error with {$field}: {$message}\n";
    }
} catch (AuthenticationException $e) {
    // Handle authentication errors
    echo "Authentication failed: " . $e->getMessage() . "\n";
} catch (RequestException $e) {
    // Get detailed error information with helpful suggestions
    echo ErrorHandler::getDetailedErrorMessage($e);

    // Or access specific error components
    echo "Status code: " . $e->getCode() . "\n";
    echo "Method: " . $e->getMethod() . "\n";
    echo "Endpoint: " . $e->getEndpoint() . "\n";

    // Get a friendly message with guidance based on the error
    echo $e->getFriendlyMessage() . "\n";
} catch (PandagoException $e) {
    // Handle other pandago errors
    echo "Pandago error: " . $e->getMessage() . "\n";
}
```

#### Error Types and Suggestions

The `ErrorHandler` utility provides specific suggestions for different error types:

| Error Type           | Example Suggestion                                                                    |
| -------------------- | ------------------------------------------------------------------------------------- |
| Authentication (401) | "Your authentication token may have expired or is invalid. Try refreshing the token." |
| Not Found (404)      | "The requested resource was not found. Verify the ID or path is correct."             |
| Validation (422)     | "The request data is likely invalid. Check your parameters."                          |
| Rate Limiting (429)  | "You've exceeded the rate limit. Please reduce the frequency of your requests."       |
| Server Error (500)   | "The pandago API is experiencing issues. Please try again later."                     |

The library also recognizes common error patterns, such as "outlet not found" or "order is not cancellable" and provides specific guidance for these cases.

#### Laravel Integration

With Laravel integration, you can enhance error responses:

```php
try {
    $order = Pandago::orders()->get($orderId);
    return response()->json(['order' => $order]);
} catch (RequestException $e) {
    // Log the detailed error
    \Log::error(ErrorHandler::getDetailedErrorMessage($e));

    // Return a user-friendly error message
    return response()->json([
        'error' => $e->getFriendlyMessage()
    ], $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
}
```

## Configuration Options

When creating a client via the factory method, you can pass additional options:

```php
$client = Client::make(
    'pandago:my:00000000-0000-0000-0000-000000000000', // ClientID
    '00000000-0000-0000-0000-000000000001',             // KeyID
    'pandago.api.my.*',                                 // Scope
    file_get_contents('path/to/client.pem'),            // Private Key
    'my',                                               // Country
    'sandbox',                                          // Environment
    30,                                                 // Timeout in seconds
    $logger                                             // PSR-3 Logger instance
);
```

Or use the array-based configuration:

```php
$client = Client::fromArray([
    'client_id' => 'pandago:my:00000000-0000-0000-0000-000000000000',
    'key_id' => '00000000-0000-0000-0000-000000000001',
    'scope' => 'pandago.api.my.*',
    'private_key' => file_get_contents('path/to/client.pem'),
    'country' => 'my',
    'environment' => 'sandbox',
    'timeout' => 30
], $logger);
```

## Supported Countries

The library supports multiple countries:

-   Singapore (sg)
-   Hong Kong (hk)
-   Malaysia (my)
-   Thailand (th)
-   Philippines (ph)
-   Taiwan (tw)
-   Pakistan (pk)
-   Jordan (jo)
-   Finland (fi)
-   Kuwait (kw)
-   Norway (no)
-   Sweden (se)

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/awesome-feature`)
3. Commit your changes (`git commit -m 'Add awesome feature'`)
4. Push to the branch (`git push origin feature/awesome-feature`)
5. Create a Pull Request

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

## Security

If you discover any security vulnerabilities, please email gua@navins.biz instead of using the issue tracker.

## License

This library is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Credits

[All Contributors](../../contributors)
