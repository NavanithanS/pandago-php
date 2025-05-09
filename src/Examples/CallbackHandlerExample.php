<?php
namespace Nava\Pandago\Examples;

use Nava\Pandago\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Example Callback Handler for Pandago Webhooks
 *
 * This class demonstrates how to implement a webhook handler
 * for receiving and processing Pandago order status callbacks
 * in a production environment.
 *
 * How to use:
 * 1. Register this endpoint with Pandago
 * 2. Configure it to handle POST requests at your desired URL
 * 3. Process callbacks and update your application state
 */
class CallbackHandlerExample
{
    /**
     * @var \Nava\Pandago\PandagoClient
     */
    private $pandagoClient;

    /**
     * @var string Path to a log file for callback records
     */
    private $logFile;

    /**
     * Constructor.
     *
     * @param array $pandagoConfig Pandago client configuration
     * @param string $logFile Path to log file (optional)
     */
    public function __construct(array $pandagoConfig, string $logFile = null)
    {
        $this->pandagoClient = Client::fromArray($pandagoConfig);
        $this->logFile       = $logFile ?: sys_get_temp_dir() . '/pandago_callbacks.log';
    }

    /**
     * Handle the incoming webhook request.
     *
     * @param ServerRequestInterface $request PSR-7 HTTP request
     * @return ResponseInterface PSR-7 HTTP response
     */
    public function handleRequest(ServerRequestInterface $request)
    {
        // Check HTTP method is POST
        if ($request->getMethod() !== 'POST') {
            return $this->createJsonResponse([
                'success' => false,
                'error'   => 'Method not allowed',
            ], 405);
        }

        // Parse the request body
        $payload = json_decode((string) $request->getBody(), true);

        if (! $payload || ! is_array($payload)) {
            return $this->createJsonResponse([
                'success' => false,
                'error'   => 'Invalid JSON payload',
            ], 400);
        }

        // Log the callback
        $this->logCallback($payload);

        // Extract order information
        $orderId       = $payload['order_id'] ?? null;
        $clientOrderId = $payload['client_order_id'] ?? null;
        $status        = $payload['status'] ?? null;

        // Validate required fields
        if (! $orderId || ! $status) {
            return $this->createJsonResponse([
                'success' => false,
                'error'   => 'Missing required fields',
            ], 400);
        }

        // Process based on status
        switch ($status) {
            case 'NEW':
                $this->handleNewOrder($payload);
                break;

            case 'RECEIVED':
                $this->handleOrderReceived($payload);
                break;

            case 'COURIER_ACCEPTED_DELIVERY':
                $this->handleCourierAccepted($payload);
                break;

            case 'COURIER_PICKUP_ARRIVAL':
                $this->handleCourierPickupArrival($payload);
                break;

            case 'COURIER_PICKUP_COMPLETE':
                $this->handleCourierPickupComplete($payload);
                break;

            case 'COURIER_ARRIVAL':
                $this->handleCourierArrival($payload);
                break;

            case 'COURIER_DELIVERED':
                $this->handleCourierDelivered($payload);
                break;

            case 'CANCELLED':
                $this->handleOrderCancelled($payload);
                break;

            default:
                $this->handleOtherStatus($payload);
                break;
        }

        // Return success response
        return $this->createJsonResponse([
            'success' => true,
            'message' => "Callback processed successfully for order {$orderId}",
        ], 200);
    }

    /**
     * Handle NEW status callback.
     *
     * @param array $payload The callback payload
     * @return void
     */
    private function handleNewOrder(array $payload): void
    {
        // Implementation for NEW status
        // Example: Update order status in your database
        $this->logStatusChange($payload, 'NEW', 'Order created');
    }

    /**
     * Handle RECEIVED status callback.
     *
     * @param array $payload The callback payload
     * @return void
     */
    private function handleOrderReceived(array $payload): void
    {
        // Implementation for RECEIVED status
        // Example: Update order status in your database
        $this->logStatusChange($payload, 'RECEIVED', 'Order received by Pandago system');
    }

    /**
     * Handle COURIER_ACCEPTED_DELIVERY status callback.
     *
     * @param array $payload The callback payload
     * @return void
     */
    private function handleCourierAccepted(array $payload): void
    {
        // Extract driver ID
        $driverId = $payload['driver']['id'] ?? null;

        if ($driverId) {
            // Get full order details including driver information
            try {
                $orderId = $payload['order_id'];
                $order   = $this->pandagoClient->orders()->get($orderId);

                // Extract complete driver details
                $driver = $order->getDriver();

                // Process driver information
                $this->logStatusChange(
                    $payload,
                    'COURIER_ACCEPTED_DELIVERY',
                    "Delivery accepted by driver: {$driver['name']} (ID: {$driver['id']})"
                );

                // Example: Update your database with driver information
                // $this->updateOrderDriver($orderId, $driver);
            } catch (\Exception $e) {
                $this->logError("Error fetching order details: " . $e->getMessage());
                $this->logStatusChange($payload, 'COURIER_ACCEPTED_DELIVERY', "Delivery accepted by driver ID: {$driverId}");
            }
        } else {
            $this->logStatusChange($payload, 'COURIER_ACCEPTED_DELIVERY', "Delivery accepted by courier (no driver ID provided)");
        }
    }

    /**
     * Handle COURIER_PICKUP_ARRIVAL status callback.
     *
     * @param array $payload The callback payload
     * @return void
     */
    private function handleCourierPickupArrival(array $payload): void
    {
        // Implementation for COURIER_PICKUP_ARRIVAL status
        $this->logStatusChange($payload, 'COURIER_PICKUP_ARRIVAL', 'Courier arrived at pickup location');
    }

    /**
     * Handle COURIER_PICKUP_COMPLETE status callback.
     *
     * @param array $payload The callback payload
     * @return void
     */
    private function handleCourierPickupComplete(array $payload): void
    {
        // Implementation for COURIER_PICKUP_COMPLETE status
        $this->logStatusChange($payload, 'COURIER_PICKUP_COMPLETE', 'Courier completed pickup');
    }

    /**
     * Handle COURIER_ARRIVAL status callback.
     *
     * @param array $payload The callback payload
     * @return void
     */
    private function handleCourierArrival(array $payload): void
    {
        // Implementation for COURIER_ARRIVAL status
        $this->logStatusChange($payload, 'COURIER_ARRIVAL', 'Courier arrived at delivery location');
    }

    /**
     * Handle COURIER_DELIVERED status callback.
     *
     * @param array $payload The callback payload
     * @return void
     */
    private function handleCourierDelivered(array $payload): void
    {
        // Implementation for COURIER_DELIVERED status
        $this->logStatusChange($payload, 'COURIER_DELIVERED', 'Order successfully delivered');

        // Example: Update your order as completed
        // $this->completeOrder($payload['order_id']);

        // Example: Fetch delivery details including proof of delivery
        try {
            $order           = $this->pandagoClient->orders()->get($payload['order_id']);
            $proofOfDelivery = $order->getProofOfDeliveryUrl();

            if ($proofOfDelivery) {
                $this->log("Proof of delivery URL: {$proofOfDelivery}");

                // Example: Save proof of delivery URL
                // $this->saveProofOfDelivery($payload['order_id'], $proofOfDelivery);
            }
        } catch (\Exception $e) {
            $this->logError("Error fetching delivery details: " . $e->getMessage());
        }
    }

    /**
     * Handle CANCELLED status callback.
     *
     * @param array $payload The callback payload
     * @return void
     */
    private function handleOrderCancelled(array $payload): void
    {
        // Implementation for CANCELLED status
        $this->logStatusChange($payload, 'CANCELLED', 'Order was cancelled');

        // Example: Update your order as cancelled
        // $this->cancelOrder($payload['order_id']);
    }

    /**
     * Handle any other status callback.
     *
     * @param array $payload The callback payload
     * @return void
     */
    private function handleOtherStatus(array $payload): void
    {
        // Implementation for other statuses
        $status = $payload['status'] ?? 'UNKNOWN';
        $this->logStatusChange($payload, $status, "Order status changed to {$status}");
    }

    /**
     * Create a JSON response.
     *
     * @param array $data The response data
     * @param int $statusCode The HTTP status code
     * @return ResponseInterface
     */
    private function createJsonResponse(array $data, int $statusCode)
    {
        // In a real implementation, you would use your framework's response object
        // This is just a placeholder for the example
        $response = [
            'status'  => $statusCode,
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode($data),
        ];

        return $response;
    }

    /**
     * Log a callback to the log file.
     *
     * @param array $payload The callback payload
     * @return void
     */
    private function logCallback(array $payload): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $orderId   = $payload['order_id'] ?? 'unknown';
        $status    = $payload['status'] ?? 'unknown';

        $logMessage = "[{$timestamp}] Callback received for order {$orderId} - Status: {$status}" . PHP_EOL;
        $logMessage .= json_encode($payload, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;

        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Log a status change.
     *
     * @param array $payload The callback payload
     * @param string $status The status
     * @param string $message Additional message
     * @return void
     */
    private function logStatusChange(array $payload, string $status, string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $orderId   = $payload['order_id'] ?? 'unknown';

        $logMessage = "[{$timestamp}] Order {$orderId} - {$status}: {$message}" . PHP_EOL;

        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Log an error.
     *
     * @param string $message Error message
     * @return void
     */
    private function logError(string $message): void
    {
        $timestamp  = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] ERROR: {$message}" . PHP_EOL;

        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Simple log message.
     *
     * @param string $message Message to log
     * @return void
     */
    private function log(string $message): void
    {
        $timestamp  = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;

        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
}
