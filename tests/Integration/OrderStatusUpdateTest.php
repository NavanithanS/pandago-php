<?php
namespace Nava\Pandago\Tests\Integration;

use Nava\Pandago\Client;
use Nava\Pandago\Config;
use Nava\Pandago\Exceptions\RequestException;
use Nava\Pandago\Models\Contact;
use Nava\Pandago\Models\Location;
use Nava\Pandago\Models\Order\CancelOrderRequest;
use Nava\Pandago\Models\Order\CreateOrderRequest;
use Nava\Pandago\Models\Order\Order;
use Nava\Pandago\Tests\TestCase;

/**
 * Test Cases for Order Status Updates
 *
 * 6.1.1: Get order status update using Get Specific Order API (Happy Path)
 * 6.1.2: Get order status update using wrong order ID (Unhappy Path)
 */
class OrderStatusUpdateTest extends TestCase
{
    /**
     * @var \Nava\Pandago\PandagoClient
     */
    protected $client;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string|null
     */
    protected $testOrderId;

    /**
     * Setup before tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Skip integration tests if required config values are missing
        if (! $this->checkRequiredConfig(['client_id', 'key_id', 'scope'])) {
            $this->markTestSkipped(
                'Integration tests require API credentials. Set them in tests/config.php to run the tests.'
            );
        }

        $this->config = Config::fromArray($this->getConfig());
        $this->client = Client::fromArray($this->getConfig());
    }

    /**
     * Teardown after tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // If a test order was created and not cancelled, attempt to cancel it
        if ($this->testOrderId) {
            try {
                $this->client->orders()->cancel(
                    $this->testOrderId,
                    new CancelOrderRequest('MISTAKE_ERROR')
                );
            } catch (\Exception $e) {
                // Ignore any errors during cleanup
            }

            $this->testOrderId = null;
        }

        parent::tearDown();
    }

    /**
     * Test Case 6.1.1: Get order status update using Get Specific Order API (Happy Path)
     *
     * Using the order id value from Submit New Order API response, get the current status
     * update of an order. The sandbox will update order status automatically.
     *
     * Steps:
     * 1. [GET] Request to /orders/{order_id} endpoint
     * 2. Use authorization token
     * 3. Expect 200 OK response with current status
     * 4. Send new get request to see status change
     *
     * @return void
     */
    public function testGetOrderStatusUpdate()
    {
        echo "\n\n✅ TEST CASE 6.1.1: Get order status update using Get Specific Order API (Happy Path)\n";
        echo "=============================================================================\n\n";
        echo "STEP 1: Create a test order to monitor status updates\n";
        echo "--------------------------------------------------\n";

        // Create test order
        $recipientLocation = new Location(
            '20 Esplanade Drive', // Address
            1.2857488,            // Latitude - Singapore
            103.8548608           // Longitude - Singapore
        );
        $recipient = new Contact('Merlion', '+6500000000', $recipientLocation);
        echo "• Recipient created with location at coordinates: 1.2857488, 103.8548608\n";

        $clientOrderId = 'test-status-' . uniqid();
        $request       = new CreateOrderRequest(
            $recipient,
            23.50,
            'Status Update Test Order'
        );
        $request->setClientOrderId($clientOrderId);

        // Set sender information
        $senderLocation = new Location(
            '1 2nd Street #08-01', // Address
            1.2923742,             // Latitude - Singapore
            103.8486029            // Longitude - Singapore
        );
        $sender = new Contact(
            'Pandago',
            '+6500000000',
            $senderLocation
        );
        $request->setSender($sender);
        echo "• Sender information added with location at coordinates: 1.2923742, 103.8486029\n";

        try {
            // Create the order to get an order ID
            $order             = $this->client->orders()->create($request);
            $this->testOrderId = $order->getOrderId();

            echo "✓ Test order created successfully\n";
            echo "• Order ID: " . $this->testOrderId . "\n";
            echo "• Initial status: " . $order->getStatus() . "\n";

            // Verify basic order details
            $this->assertNotEmpty($this->testOrderId, 'Order ID should not be empty');
            $this->assertEquals($clientOrderId, $order->getClientOrderId(), 'Client Order ID should match');

            echo "\nSTEP 2: Define API URL for retrieving order status\n";
            echo "----------------------------------------------\n";

            // Define and display the full URL based on environment
            $environment = $this->config->getEnvironment();
            $country     = $this->config->getCountry();

            // URLs as specified in the documentation
            $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/orders/{$this->testOrderId}";
            $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/orders/{$this->testOrderId}";
            $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/orders/{$this->testOrderId}";

            // Determine the actual URL to use
            $fullUrl = $sandboxUrl;
            if ('production' === $environment) {
                $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
            }

            echo "• Full URL: " . $fullUrl . "\n";
            echo "• HTTP Method: GET\n";
            echo "• Environment: " . $environment . "\n";
            echo "• Country: " . $country . "\n";

            echo "\nSTEP 3: Make first GET request to retrieve order status\n";
            echo "----------------------------------------------------\n";

            // First status check
            $start      = microtime(true);
            $checkOrder = $this->client->orders()->get($this->testOrderId);
            $end        = microtime(true);

            echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";
            echo "✓ Response status: 200 OK\n";
            echo "• Current order status: " . $checkOrder->getStatus() . "\n";

            // Return full order details for analysis
            $orderDetails = $checkOrder->toArray();
            echo "• Order details JSON:\n";
            echo json_encode($orderDetails, JSON_PRETTY_PRINT) . "\n";

            // Verify the order status and other key fields
            $this->assertNotEmpty($checkOrder->getStatus(), 'Order status should not be empty');
            $this->assertEquals($this->testOrderId, $checkOrder->getOrderId(), 'Order ID should match');
            $this->assertEquals($clientOrderId, $checkOrder->getClientOrderId(), 'Client Order ID should match');

            echo "\nSTEP 4: Wait and make second GET request to check for status changes\n";
            echo "----------------------------------------------------------------\n";
            echo "• Waiting 5 seconds before checking status again...\n";

            // Wait a few seconds to check if status has changed (sandbox might update it automatically)
            sleep(5);

            // Second status check
            $start        = microtime(true);
            $updatedOrder = $this->client->orders()->get($this->testOrderId);
            $end          = microtime(true);

            echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";
            echo "✓ Response status: 200 OK\n";
            echo "• Updated order status: " . $updatedOrder->getStatus() . "\n";

            // Log whether the status has changed
            if ($updatedOrder->getStatus() !== $checkOrder->getStatus()) {
                echo "✓ Status has changed from {$checkOrder->getStatus()} to {$updatedOrder->getStatus()}\n";
            } else {
                echo "• Status has not changed (still {$updatedOrder->getStatus()})\n";
                echo "• Note: In sandbox, automatic status transitions may take longer than 5 seconds\n";
            }

            echo "\nSUMMARY: Successfully retrieved order status updates\n";
            echo "=================================================\n";
            echo "• API Endpoint: " . $fullUrl . "\n";
            echo "• Order ID: " . $this->testOrderId . "\n";
            echo "• Initial status: " . $order->getStatus() . "\n";
            echo "• First check status: " . $checkOrder->getStatus() . "\n";
            echo "• Second check status: " . $updatedOrder->getStatus() . "\n";

        } catch (RequestException $e) {
            // Handle common errors
            if ($e->getCode() === 404 && strpos($e->getMessage(), 'Outlet not found') !== false) {
                echo "⚠️ Test skipped: Outlet not found\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                echo "• This usually means the client vendor ID is not properly configured\n";
                $this->markTestSkipped('Outlet not found. Please configure a valid client vendor ID.');
            } elseif ($e->getCode() === 422 && strpos($e->getMessage(), 'No Branch found') !== false) {
                echo "⚠️ Test skipped: No branch found close enough to the sender coordinates\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                echo "• Consider using coordinates that are within a delivery area\n";
                $this->markTestSkipped('No branch found close to sender coordinates');
            } else {
                echo "❌ Test failed with error:\n";
                echo "• Status code: " . $e->getCode() . "\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                throw $e;
            }
        }
    }

    /**
     * Test Case 6.1.2: Get order status update using wrong order ID (Unhappy Path)
     *
     * Send request using the wrong order id to verify proper error handling.
     *
     * Steps:
     * 1. Same steps as 6.1.1
     * 2. Use the wrong order id in request parameter
     * 3. Response expected: 404 Not Found with message "Order not found"
     *
     * @return void
     */
    public function testGetOrderStatusWithWrongId()
    {
        echo "\n\n✅ TEST CASE 6.1.2: Get order status update using wrong order ID (Unhappy Path)\n";
        echo "=========================================================================\n\n";
        echo "STEP 1: Define an invalid order ID\n";
        echo "--------------------------------\n";

        // Generate an invalid order ID format based on the expected format from Pandago
        $wrongOrderId = 'invalid-' . uniqid();
        echo "• Invalid order ID generated: " . $wrongOrderId . "\n";

        echo "\nSTEP 2: Define API URL for retrieving order status\n";
        echo "----------------------------------------------\n";

        // Define and display the full URL based on environment
        $environment = $this->config->getEnvironment();
        $country     = $this->config->getCountry();

        // URLs as specified in the documentation
        $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/orders/{$wrongOrderId}";
        $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/orders/{$wrongOrderId}";
        $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/orders/{$wrongOrderId}";

        // Determine the actual URL to use
        $fullUrl = $sandboxUrl;
        if ('production' === $environment) {
            $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
        }

        echo "• Full URL: " . $fullUrl . "\n";
        echo "• HTTP Method: GET\n";
        echo "• Environment: " . $environment . "\n";
        echo "• Country: " . $country . "\n";

        echo "\nSTEP 3: Make GET request with invalid order ID\n";
        echo "--------------------------------------------\n";

        try {
            // Attempt to retrieve order status with wrong ID
            $start = microtime(true);
            $order = $this->client->orders()->get($wrongOrderId);
            $end   = microtime(true);

            // If we reach here, the test has failed
            echo "❌ Test failed: Expected RequestException was not thrown\n";
            echo "• Response received: " . json_encode($order->toArray(), JSON_PRETTY_PRINT) . "\n";
            $this->fail('Expected RequestException for wrong order ID was not thrown');

        } catch (RequestException $e) {
            $end = microtime(true);
            echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";

            echo "\nSTEP 4: Verify the error response\n";
            echo "--------------------------------\n";

            // Verify the status code is 404 Not Found
            $this->assertEquals(
                404,
                $e->getCode(),
                'Expected HTTP 404 Not Found status code'
            );
            echo "✓ Response status: 404 Not Found - Correct error code for wrong order ID\n";

            // Verify the error message
            $this->assertStringContainsString(
                'not found',
                strtolower($e->getMessage()),
                'Error message should indicate the order is not found'
            );
            echo "✓ Error message correctly indicates that the order is not found\n";

            // Print details about the error for debugging
            echo "• Complete error message: " . $e->getMessage() . "\n";

            if ($e->getData()) {
                echo "• Error data: " . json_encode($e->getData(), JSON_PRETTY_PRINT) . "\n";
            }

            echo "\nSUMMARY: Successfully validated error for wrong order ID\n";
            echo "====================================================\n";
            echo "• API Endpoint: " . $fullUrl . "\n";
            echo "• Correctly received error status code 404\n";
            echo "• API properly rejected the request with 'Order not found' message\n";
        }
    }

    /**
     * Test monitoring order status transitions over time.
     *
     * This test creates an order, then polls the status at intervals to observe
     * the progression of status changes in the sandbox environment.
     *
     * @return void
     */
    public function testMonitorOrderStatusTransitions()
    {
        echo "\n\n✅ ADDITIONAL TEST: Monitor order status transitions over time\n";
        echo "===========================================================\n\n";
        echo "STEP 1: Create a test order to monitor status transitions\n";
        echo "-----------------------------------------------------\n";

        // Create test order (same setup as the first test)
        $recipientLocation = new Location(
            '20 Esplanade Drive',
            1.2857488,
            103.8548608
        );
        $recipient = new Contact('Merlion', '+6500000000', $recipientLocation);

        $clientOrderId = 'test-transitions-' . uniqid();
        $request       = new CreateOrderRequest(
            $recipient,
            23.50,
            'Status Transition Test Order'
        );
        $request->setClientOrderId($clientOrderId);

        // Set sender information
        $senderLocation = new Location(
            '1 2nd Street #08-01',
            1.2923742,
            103.8486029
        );
        $sender = new Contact(
            'Pandago',
            '+6500000000',
            $senderLocation
        );
        $request->setSender($sender);

        try {
            // Create the order to track
            $order             = $this->client->orders()->create($request);
            $this->testOrderId = $order->getOrderId();

            echo "✓ Test order created successfully\n";
            echo "• Order ID: " . $this->testOrderId . "\n";
            echo "• Initial status: " . $order->getStatus() . "\n";

            echo "\nSTEP 2: Poll for status changes at 5-second intervals\n";
            echo "---------------------------------------------------\n";

            // Track observed statuses
            $observedStatuses = [$order->getStatus()];
            $previousStatus   = $order->getStatus();

            // Poll for status changes (3 times with 5-second intervals)
            $pollAttempts = 3;
            for ($i = 1; $i <= $pollAttempts; $i++) {
                echo "• Poll attempt $i of $pollAttempts (waiting 5 seconds)...\n";
                sleep(5);

                $currentOrder  = $this->client->orders()->get($this->testOrderId);
                $currentStatus = $currentOrder->getStatus();

                if ($currentStatus !== $previousStatus) {
                    echo "✓ Status transition detected: $previousStatus → $currentStatus\n";
                    $observedStatuses[] = $currentStatus;
                    $previousStatus     = $currentStatus;
                } else {
                    echo "• No status change detected (still $currentStatus)\n";
                }

                // Output other relevant information from the order
                if (isset($currentOrder->getDriver()['id'])) {
                    echo "• Driver ID: " . $currentOrder->getDriver()['id'] . "\n";
                }

                if ($currentOrder->getTrackingLink()) {
                    echo "• Tracking Link: " . $currentOrder->getTrackingLink() . "\n";
                }
            }

            echo "\nSUMMARY: Order status transition monitoring\n";
            echo "=========================================\n";
            echo "• Order ID: " . $this->testOrderId . "\n";
            echo "• Observed status transitions: " . implode(" → ", $observedStatuses) . "\n";
            echo "• Total unique statuses observed: " . count($observedStatuses) . "\n";
            echo "• Note: In the sandbox environment, status transitions may occur at varying speeds\n";
            echo "• Note: A production order would typically progress through statuses like:\n";
            echo "  NEW → RECEIVED → COURIER_ACCEPTED_DELIVERY → COURIER_PICKUP_ARRIVAL → \n";
            echo "  COURIER_PICKUP_COMPLETE → COURIER_ARRIVAL → COURIER_DELIVERED\n";

        } catch (RequestException $e) {
            // Handle common errors
            if ($e->getCode() === 404 && strpos($e->getMessage(), 'Outlet not found') !== false) {
                echo "⚠️ Test skipped: Outlet not found\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                $this->markTestSkipped('Outlet not found. Please configure a valid client vendor ID.');
            } else {
                echo "❌ Test failed with error:\n";
                echo "• Status code: " . $e->getCode() . "\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                throw $e;
            }
        }
    }
}
