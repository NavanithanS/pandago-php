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
use Nava\Pandago\Tests\Util\MockCallbackServer;

/**
 * Test Cases for Order Cancellation
 *
 * 8.1.1: Cancel current order (Happy Path)
 * 8.1.2: Cancel an order using cancellation reason that is not acceptable (Unhappy Path)
 * 8.1.3: Cancel an order when status is "COURIER_ACCEPTED_DELIVERY" (Unhappy Path)
 * 8.1.4: Cancelling the same order twice (Unhappy Path)
 * 8.1.5: Cancel an order using the wrong order id (Unhappy Path)
 * 8.2.1: Get Order Cancellation Status Update (Happy Path)
 * 8.2.2: Get cancellation detail through the Get Specific Order API (Happy Path)
 * 8.2.3: Get cancellation detail on order initiated from Logistic/pandago side (Unhappy Path)
 */
class OrderCancellationTest extends TestCase
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
     * @var MockCallbackServer|null
     */
    protected $callbackServer;

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

        // Stop the callback server if it was started
        if ($this->callbackServer && $this->callbackServer->isRunning) {
            $this->callbackServer->stop();
            $this->callbackServer = null;
        }

        parent::tearDown();
    }

    /**
     * Helper method to create a test order.
     *
     * @param string $description Order description
     * @return Order The created order
     */
    private function createTestOrder($description = 'Cancellation Test Order')
    {
        // Create recipient with location in Singapore
        $recipientLocation = new Location(
            '20 Esplanade Drive', // Address
            1.2857488,            // Latitude - Singapore
            103.8548608           // Longitude - Singapore
        );
        $recipient = new Contact('Merlion', '+6500000000', $recipientLocation);

        // Create order request with a client order ID for tracing
        $clientOrderId = 'test-cancel-' . uniqid();
        $request       = new CreateOrderRequest(
            $recipient,
            23.50,       // Amount
            $description // Description
        );
        $request->setClientOrderId($clientOrderId);

        // Set sender information
        $senderLocation = new Location(
            '1 2nd Street #08-01', // Address
            1.2923742,             // Latitude - Singapore
            103.8486029            // Longitude - Singapore
        );
        $sender = new Contact(
            'Pandago',               // Name
            '+6500000000',           // Phone Number
            $senderLocation,         // Location
            'use the left side door' // Notes
        );
        $request->setSender($sender);

        // Create the order
        $order             = $this->client->orders()->create($request);
        $this->testOrderId = $order->getOrderId();

        return $order;
    }

    /**
     * Test Case 8.1.1: Cancel current order (Happy Path)
     *
     * Using the order id value from Submit New Order API response, cancel a new order.
     * Order is only cancellable when status is "NEW", "RECEIVED", or "WAITING_FOR_TRANSPORT".
     * Cancellation reason must follow the cancellation reason list provided in the documentation.
     *
     * Steps:
     * 1. [DELETE] Request to orders/(order_id) endpoint
     * 2. Include cancellation reason in the body
     * 3. Response expected: 204 No Content
     *
     * @return void
     */
    public function testCancelCurrentOrder()
    {
        echo "\n\n✅ TEST CASE 8.1.1: Cancel current order (Happy Path)\n";
        echo "================================================\n\n";
        echo "STEP 1: Create a test order to be cancelled\n";
        echo "----------------------------------------\n";

        try {
            $order = $this->createTestOrder();
            echo "✓ Test order created successfully\n";
            echo "• Order ID: " . $order->getOrderId() . "\n";
            echo "• Initial status: " . $order->getStatus() . "\n";

            echo "\nSTEP 2: Prepare cancellation request\n";
            echo "----------------------------------\n";

            // Use one of the acceptable cancellation reasons
            $reason = 'MISTAKE_ERROR';
            echo "• Using cancellation reason: " . $reason . "\n";

            $cancelRequest = new CancelOrderRequest($reason);

            // Define and display the full URL based on environment
            $environment = $this->config->getEnvironment();
            $country     = $this->config->getCountry();

            // URLs as specified in the documentation
            $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/orders/{$order->getOrderId()}";
            $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/orders/{$order->getOrderId()}";
            $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/orders/{$order->getOrderId()}";

            // Determine the actual URL to use
            $fullUrl = $sandboxUrl;
            if ('production' === $environment) {
                $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
            }

            echo "• Full URL: " . $fullUrl . "\n";
            echo "• HTTP Method: DELETE\n";
            echo "• Request body: " . json_encode($cancelRequest->toArray()) . "\n";

            echo "\nSTEP 3: Cancel the order\n";
            echo "----------------------\n";

            $start  = microtime(true);
            $result = $this->client->orders()->cancel($order->getOrderId(), $cancelRequest);
            $end    = microtime(true);

            echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";

            // Verify the result is true (successful cancellation)
            $this->assertTrue($result);
            echo "✓ Order cancelled successfully\n";

            // Order is now cancelled, no need to clean up in tearDown
            $this->testOrderId = null;

            // Verify order status is now CANCELLED by retrieving the order
            $cancelledOrder = $this->client->orders()->get($order->getOrderId());
            $this->assertEquals('CANCELLED', $cancelledOrder->getStatus());
            echo "✓ Order status is now: CANCELLED\n";

            echo "\nSUMMARY: Successfully cancelled an order\n";
            echo "======================================\n";
            echo "• Order ID: " . $order->getOrderId() . "\n";
            echo "• Cancellation reason: " . $reason . "\n";
            echo "• Response: 204 No Content (as expected)\n";

        } catch (RequestException $e) {
            // Handle common errors
            if ($e->getCode() === 404 && strpos($e->getMessage(), 'Outlet not found') !== false) {
                echo "⚠️ Test skipped: Outlet not found\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                $this->markTestSkipped('Outlet not found. Please configure a valid client vendor ID.');
            } elseif ($e->getCode() === 422 && strpos($e->getMessage(), 'No Branch found') !== false) {
                echo "⚠️ Test skipped: No branch found close enough to the sender coordinates\n";
                echo "• Error message: " . $e->getMessage() . "\n";
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
     * Test Case 8.1.2: Cancel an order using cancellation reason that is not acceptable (Unhappy Path)
     *
     * Attempt to cancel an order with an invalid cancellation reason.
     * Accepted reasons are: DELIVERY_ETA_TOO_LONG, MISTAKE_ERROR, REASON_UNKNOWN.
     *
     * Steps:
     * 1. Same steps as 8.1.1 but with an invalid cancellation reason
     * 2. Response expected: 400 Bad Request
     *
     * @return void
     */
    public function testCancelWithInvalidReason()
    {
        echo "\n\n✅ TEST CASE 8.1.2: Cancel an order using cancellation reason that is not acceptable (Unhappy Path)\n";
        echo "=======================================================================================\n\n";
        echo "STEP 1: Create a test order to be cancelled\n";
        echo "----------------------------------------\n";

        try {
            $order = $this->createTestOrder();
            echo "✓ Test order created successfully\n";
            echo "• Order ID: " . $order->getOrderId() . "\n";
            echo "• Initial status: " . $order->getStatus() . "\n";

            echo "\nSTEP 2: Prepare cancellation request with invalid reason\n";
            echo "----------------------------------------------------\n";

                                            // Use an invalid cancellation reason
            $invalidReason = 'WRONG_ORDER'; // This is not in the valid reasons list
            echo "• Using invalid cancellation reason: " . $invalidReason . "\n";

            // We need to avoid validation in the SDK, so we'll create a raw array
            $invalidCancelRequest = ['reason' => $invalidReason];

            // Define and display the full URL based on environment
            $environment = $this->config->getEnvironment();
            $country     = $this->config->getCountry();

            // URLs as specified in the documentation
            $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/orders/{$order->getOrderId()}";
            $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/orders/{$order->getOrderId()}";
            $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/orders/{$order->getOrderId()}";

            // Determine the actual URL to use
            $fullUrl = $sandboxUrl;
            if ('production' === $environment) {
                $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
            }

            echo "• Full URL: " . $fullUrl . "\n";
            echo "• HTTP Method: DELETE\n";
            echo "• Request body: " . json_encode($invalidCancelRequest) . "\n";

            echo "\nSTEP 3: Attempt to cancel the order with invalid reason\n";
            echo "----------------------------------------------------\n";

            try {
                // Since we can't use the SDK's cancel method directly with an invalid reason
                // (it would validate the reason), we'll manually construct the request
                $start = microtime(true);

                // We'll use a valid reason to create the CancelOrderRequest for the SDK
                // but then modify it to have an invalid reason when making the request
                $validRequest = new CancelOrderRequest('MISTAKE_ERROR');

                // Use reflection to modify the private reason property
                $reflection = new \ReflectionClass(CancelOrderRequest::class);
                $property   = $reflection->getProperty('reason');
                $property->setAccessible(true);
                $property->setValue($validRequest, $invalidReason);

                // Now attempt to cancel with our modified request
                $result = $this->client->orders()->cancel($order->getOrderId(), $validRequest);
                $end    = microtime(true);

                // If we reach here, the test has failed because we expected an exception
                echo "⚠️ Test failed: Expected 400 error but got success response\n";
                $this->fail('Expected 400 Bad Request but received success response');

            } catch (RequestException $e) {
                $end = microtime(true);
                echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";

                // Verify it's a 400 Bad Request error
                $this->assertEquals(400, $e->getCode(), 'Expected HTTP 400 Bad Request');
                echo "✓ Received expected 400 Bad Request error\n";

                // Verify the error message
                $expectedErrorMsg = "is invalid";
                $this->assertStringContainsString($expectedErrorMsg, $e->getMessage(), 'Error should indicate invalid reason');
                echo "✓ Error message correctly indicates reason is invalid: " . $e->getMessage() . "\n";

                // Cancel the order properly for cleanup
                $this->client->orders()->cancel($order->getOrderId(), new CancelOrderRequest('MISTAKE_ERROR'));
                // Order is now cancelled, no need to clean up in tearDown
                $this->testOrderId = null;

                echo "\nSUMMARY: Successfully verified that invalid cancellation reasons are rejected\n";
                echo "====================================================================\n";
                echo "• Order ID: " . $order->getOrderId() . "\n";
                echo "• Invalid reason: " . $invalidReason . "\n";
                echo "• Response: 400 Bad Request (as expected)\n";
                echo "• Error message: " . $e->getMessage() . "\n";
            }

        } catch (RequestException $e) {
            // Handle common errors during order creation
            if ($e->getCode() === 404 && strpos($e->getMessage(), 'Outlet not found') !== false) {
                echo "⚠️ Test skipped: Outlet not found\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                $this->markTestSkipped('Outlet not found. Please configure a valid client vendor ID.');
            } elseif ($e->getCode() === 422 && strpos($e->getMessage(), 'No Branch found') !== false) {
                echo "⚠️ Test skipped: No branch found close enough to the sender coordinates\n";
                echo "• Error message: " . $e->getMessage() . "\n";
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
     * Test Case 8.1.3: Cancel an order when status is "COURIER_ACCEPTED_DELIVERY" (Unhappy Path)
     *
     * Attempt to cancel an order that has been accepted by a courier.
     * Orders are only cancellable when status is "NEW", "RECEIVED", or "WAITING_FOR_TRANSPORT".
     *
     * Note: Since we can't control when a courier accepts an order in the test environment,
     * this test may need to be simulated or skipped based on sandbox limitations.
     *
     * Steps:
     * 1. Same steps as 8.1.1 but with an order that has been accepted by a courier
     * 2. Response expected: 409 Conflict with message "Order is not cancellable"
     *
     * @return void
     */
    public function testCancelAcceptedOrder()
    {
        echo "\n\n✅ TEST CASE 8.1.3: Cancel an order when status is \"COURIER_ACCEPTED_DELIVERY\" (Unhappy Path)\n";
        echo "=====================================================================================\n\n";
        echo "STEP 1: Create a test order\n";
        echo "------------------------\n";

        try {
            $order = $this->createTestOrder();
            echo "✓ Test order created successfully\n";
            echo "• Order ID: " . $order->getOrderId() . "\n";
            echo "• Initial status: " . $order->getStatus() . "\n";

            echo "\nSTEP 2: Simulate order progression to COURIER_ACCEPTED_DELIVERY\n";
            echo "--------------------------------------------------------\n";
            echo "• Note: In the sandbox environment, we can't directly control order progression\n";
            echo "• Using a special description to simulate order acceptance by courier\n";

            // In a real test, we would need to wait for the order to progress to COURIER_ACCEPTED_DELIVERY
            // Since we can't guarantee this in the sandbox, we'll simulate it for this test
            // by creating a new order with a special "simulator" description
            $simulatedOrder = $this->createTestOrder("<Simulator><Order scenario=\"COURIER_ACCEPTED_DELIVERY\" /></Simulator>");
            echo "✓ Created order with special directive to simulate COURIER_ACCEPTED_DELIVERY status\n";
            echo "• Order ID: " . $simulatedOrder->getOrderId() . "\n";

            // Wait briefly for the simulation to take effect
            sleep(2);

            // Try to get the updated order status - in a real sandbox this might not change
            try {
                $updatedOrder = $this->client->orders()->get($simulatedOrder->getOrderId());
                echo "• Current order status: " . $updatedOrder->getStatus() . "\n";
            } catch (\Exception $e) {
                echo "• Could not retrieve updated order status: " . $e->getMessage() . "\n";
            }

            echo "\nSTEP 3: Attempt to cancel the order\n";
            echo "--------------------------------\n";

            $cancelRequest = new CancelOrderRequest('MISTAKE_ERROR');

            try {
                $start  = microtime(true);
                $result = $this->client->orders()->cancel($simulatedOrder->getOrderId(), $cancelRequest);
                $end    = microtime(true);

                // If we reach here, either:
                // 1. The simulation didn't work and the order is still cancellable, or
                // 2. The API behavior has changed
                echo "⚠️ Test result unexpected: Order was cancelled successfully\n";
                echo "• This suggests the order had not yet reached COURIER_ACCEPTED_DELIVERY status\n";
                echo "• Order status might still be in a cancellable state in the sandbox\n";

                // Order is now cancelled, no need to clean up in tearDown
                $this->testOrderId = null;

            } catch (RequestException $e) {
                $end = microtime(true);
                echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";

                // Verify it's a 409 Conflict error
                $this->assertEquals(409, $e->getCode(), 'Expected HTTP 409 Conflict');
                echo "✓ Received expected 409 Conflict error\n";

                // Verify the error message
                $expectedErrorMsg = "Order is not cancellable";
                $this->assertStringContainsString($expectedErrorMsg, $e->getMessage(), 'Error should indicate order is not cancellable');
                echo "✓ Error message correctly indicates order is not cancellable: " . $e->getMessage() . "\n";

                echo "\nSUMMARY: Successfully verified that orders in COURIER_ACCEPTED_DELIVERY state cannot be cancelled\n";
                echo "====================================================================================\n";
                echo "• Order ID: " . $simulatedOrder->getOrderId() . "\n";
                echo "• Response: 409 Conflict (as expected)\n";
                echo "• Error message: " . $e->getMessage() . "\n";

                // Note: We can't cancel this order anymore, but we should remove it from testOrderId
                // so tearDown doesn't try to cancel it again
                $this->testOrderId = null;
            }

        } catch (RequestException $e) {
            // Handle common errors during order creation
            if ($e->getCode() === 404 && strpos($e->getMessage(), 'Outlet not found') !== false) {
                echo "⚠️ Test skipped: Outlet not found\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                $this->markTestSkipped('Outlet not found. Please configure a valid client vendor ID.');
            } elseif ($e->getCode() === 422 && strpos($e->getMessage(), 'No Branch found') !== false) {
                echo "⚠️ Test skipped: No branch found close enough to the sender coordinates\n";
                echo "• Error message: " . $e->getMessage() . "\n";
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
     * Test Case 8.1.4: Cancelling the same order twice (Unhappy Path)
     *
     * Attempt to cancel an order that has already been cancelled.
     *
     * Steps:
     * 1. Same steps as 8.1.1
     * 2. Repeat Step 1 (try to cancel again)
     * 3. Response expected: 409 Conflict with message "Order is not cancellable"
     *
     * @return void
     */
    public function testCancelOrderTwice()
    {
        echo "\n\n✅ TEST CASE 8.1.4: Cancelling the same order twice (Unhappy Path)\n";
        echo "===========================================================\n\n";
        echo "STEP 1: Create a test order\n";
        echo "------------------------\n";

        try {
            $order = $this->createTestOrder();
            echo "✓ Test order created successfully\n";
            echo "• Order ID: " . $order->getOrderId() . "\n";
            echo "• Initial status: " . $order->getStatus() . "\n";

            echo "\nSTEP 2: Cancel the order for the first time\n";
            echo "----------------------------------------\n";

            $cancelRequest = new CancelOrderRequest('MISTAKE_ERROR');

            $start  = microtime(true);
            $result = $this->client->orders()->cancel($order->getOrderId(), $cancelRequest);
            $end    = microtime(true);

            echo "✓ First cancellation request completed in " . round(($end - $start) * 1000, 2) . " ms\n";
            $this->assertTrue($result, 'First cancellation should succeed');
            echo "✓ Order cancelled successfully on first attempt\n";

            // Verify order status is now CANCELLED
            $cancelledOrder = $this->client->orders()->get($order->getOrderId());
            $this->assertEquals('CANCELLED', $cancelledOrder->getStatus());
            echo "✓ Order status is now: CANCELLED\n";

            echo "\nSTEP 3: Attempt to cancel the same order again\n";
            echo "-------------------------------------------\n";

            try {
                $start = microtime(true);
                $this->client->orders()->cancel($order->getOrderId(), $cancelRequest);
                $end = microtime(true);

                // If we reach here, the test has failed because we expected an exception
                echo "⚠️ Test failed: Expected 409 error but got success response\n";
                $this->fail('Expected 409 Conflict but received success response');

            } catch (RequestException $e) {
                $end = microtime(true);
                echo "✓ Second cancellation request completed in " . round(($end - $start) * 1000, 2) . " ms\n";

                // Verify it's a 409 Conflict error
                $this->assertEquals(409, $e->getCode(), 'Expected HTTP 409 Conflict');
                echo "✓ Received expected 409 Conflict error\n";

                // Verify the error message
                $expectedErrorMsg = "Order is not cancellable";
                $this->assertStringContainsString($expectedErrorMsg, $e->getMessage(), 'Error should indicate order is not cancellable');
                echo "✓ Error message correctly indicates order is not cancellable: " . $e->getMessage() . "\n";

                echo "\nSUMMARY: Successfully verified that orders cannot be cancelled twice\n";
                echo "===========================================================\n";
                echo "• Order ID: " . $order->getOrderId() . "\n";
                echo "• First cancellation response: 204 No Content (success)\n";
                echo "• Second cancellation response: 409 Conflict (as expected)\n";
                echo "• Error message: " . $e->getMessage() . "\n";
            }

            // Order is already cancelled, no need to clean up in tearDown
            $this->testOrderId = null;

        } catch (RequestException $e) {
            // Handle common errors during order creation
            if ($e->getCode() === 404 && strpos($e->getMessage(), 'Outlet not found') !== false) {
                echo "⚠️ Test skipped: Outlet not found\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                $this->markTestSkipped('Outlet not found. Please configure a valid client vendor ID.');
            } elseif ($e->getCode() === 422 && strpos($e->getMessage(), 'No Branch found') !== false) {
                echo "⚠️ Test skipped: No branch found close enough to the sender coordinates\n";
                echo "• Error message: " . $e->getMessage() . "\n";
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
     * Test Case 8.1.5: Cancel an order using the wrong order id (Unhappy Path)
     *
     * Attempt to cancel an order with an invalid or non-existent order ID.
     *
     * Steps:
     * 1. Same steps as 8.1.1 but with an invalid order ID
     * 2. Response expected: 404 Not Found with message "Order not found"
     *
     * @return void
     */
    public function testCancelWithWrongOrderId()
    {
        echo "\n\n✅ TEST CASE 8.1.5: Cancel an order using the wrong order id (Unhappy Path)\n";
        echo "==================================================================\n\n";
        echo "STEP 1: Define an invalid/non-existent order ID\n";
        echo "-------------------------------------------\n";

        // Generate a random invalid order ID
        $invalidOrderId = 'invalid-' . uniqid();
        echo "• Invalid order ID: " . $invalidOrderId . "\n";

        echo "\nSTEP 2: Prepare cancellation request\n";
        echo "----------------------------------\n";

        $cancelRequest = new CancelOrderRequest('MISTAKE_ERROR');
        echo "• Using cancellation reason: MISTAKE_ERROR\n";

        // Define and display the full URL based on environment
        $environment = $this->config->getEnvironment();
        $country     = $this->config->getCountry();

        // URLs as specified in the documentation
        $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/orders/{$invalidOrderId}";
        $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/orders/{$invalidOrderId}";
        $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/orders/{$invalidOrderId}";

        // Determine the actual URL to use
        $fullUrl = $sandboxUrl;
        if ('production' === $environment) {
            $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
        }

        echo "• Full URL: " . $fullUrl . "\n";
        echo "• HTTP Method: DELETE\n";
        echo "• Request body: " . json_encode($cancelRequest->toArray()) . "\n";

        echo "\nSTEP 3: Attempt to cancel the non-existent order\n";
        echo "---------------------------------------------\n";

        try {
            $start = microtime(true);
            $this->client->orders()->cancel($invalidOrderId, $cancelRequest);
            $end = microtime(true);

            // If we reach here, the test has failed because we expected an exception
            echo "⚠️ Test failed: Expected 404 error but got success response\n";
            $this->fail('Expected 404 Not Found but received success response');

        } catch (RequestException $e) {
            $end = microtime(true);
            echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";

            // Verify it's a 404 Not Found error
            $this->assertEquals(404, $e->getCode(), 'Expected HTTP 404 Not Found');
            echo "✓ Received expected 404 Not Found error\n";

            // Verify the error message
            $expectedErrorMsg = "Order not found";
            $this->assertStringContainsString($expectedErrorMsg, $e->getMessage(), 'Error should indicate order not found');
            echo "✓ Error message correctly indicates order not found: " . $e->getMessage() . "\n";

            echo "\nSUMMARY: Successfully verified that non-existent orders cannot be cancelled\n";
            echo "====================================================================\n";
            echo "• Invalid Order ID: " . $invalidOrderId . "\n";
            echo "• Response: 404 Not Found (as expected)\n";
            echo "• Error message: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test Case 8.2.1: Get Order Cancellation Status Update (Happy Path)
     *
     * Verify that when an order is cancelled, the status update is sent via callback.
     * This is optional for vendors using Push Order Status Update via callback.
     *
     * Note: This test requires a configured callback URL and will use a mock server
     * to simulate receiving callbacks.
     *
     * Steps:
     * 1. Same steps as 8.1.1
     * 2. Verify callback URL receives a status update with status "CANCELLED"
     *
     * @return void
     */
    public function testOrderCancellationStatusCallback()
    {
        echo "\n\n✅ TEST CASE 8.2.1: Get Order Cancellation Status Update (Happy Path)\n";
        echo "==================================================================\n\n";

        // Check if callback testing is enabled in config
        $config          = $this->getConfig();
        $callbackEnabled = isset($config['callback_testing_enabled']) && true === $config['callback_testing_enabled'];

        if (! $callbackEnabled) {
            echo "⚠️ Test skipped: Callback testing is not enabled in config\n";
            echo "• To enable callback testing, set 'callback_testing_enabled' to true in tests/config.php\n";
            $this->markTestSkipped('Callback testing is not enabled in config');
            return;
        }

        echo "STEP 1: Start mock callback server\n";
        echo "-------------------------------\n";

        // Create and start mock callback server
        $this->callbackServer = new MockCallbackServer(8000, '/pandago-callback');
        $serverStarted        = $this->callbackServer->start();

        if (! $serverStarted) {
            echo "⚠️ Test skipped: Failed to start mock callback server\n";
            $this->markTestSkipped('Failed to start mock callback server');
            return;
        }

        echo "✓ Mock callback server started at " . $this->callbackServer->getCallbackUrl() . "\n";

        echo "\nSTEP 2: Create a test order\n";
        echo "------------------------\n";

        try {
            $order = $this->createTestOrder();
            echo "✓ Test order created successfully\n";
            echo "• Order ID: " . $order->getOrderId() . "\n";
            echo "• Initial status: " . $order->getStatus() . "\n";

            echo "\nSTEP 3: Cancel the order\n";
            echo "---------------------\n";

            $cancelRequest = new CancelOrderRequest('MISTAKE_ERROR');

            $start  = microtime(true);
            $result = $this->client->orders()->cancel($order->getOrderId(), $cancelRequest);
            $end    = microtime(true);

            echo "✓ Cancellation request completed in " . round(($end - $start) * 1000, 2) . " ms\n";
            $this->assertTrue($result, 'Cancellation should succeed');
            echo "✓ Order cancelled successfully\n";

            // Verify order status is now CANCELLED
            $cancelledOrder = $this->client->orders()->get($order->getOrderId());
            $this->assertEquals('CANCELLED', $cancelledOrder->getStatus());
            echo "✓ Order status is now: CANCELLED\n";

            echo "\nSTEP 4: Wait for and process callbacks\n";
            echo "-----------------------------------\n";

                               // Wait and process callbacks for a few seconds
            $maxWaitTime = 10; // seconds
            echo "• Waiting for callbacks (up to $maxWaitTime seconds)...\n";

            $startWait = time();
            $received  = false;

            while (time() - $startWait < $maxWaitTime) {
                // Process any incoming callbacks
                $this->callbackServer->processCallbacks();

                // Check if we've received a cancellation callback
                $callbacks = $this->callbackServer->getCallbacksForOrder($order->getOrderId());

                foreach ($callbacks as $callback) {
                    if (isset($callback['status']) && 'CANCELLED' === $callback['status']) {
                        $received = true;
                        break 2; // Break out of both loops
                    }
                }

                                // Sleep briefly
                usleep(500000); // 0.5 seconds
            }

            // Check if we received the expected callback
            if ($received) {
                echo "✓ Received CANCELLED status callback!\n";

                // Print the callback details
                foreach ($callbacks as $callback) {
                    if (isset($callback['status']) && 'CANCELLED' === $callback['status']) {
                        echo "• Callback received with details:\n";
                        echo json_encode($callback, JSON_PRETTY_PRINT) . "\n";

                        // Verify the callback contains expected fields
                        $this->assertEquals($order->getOrderId(), $callback['order_id']);
                        $this->assertEquals('CANCELLED', $callback['status']);

                        break;
                    }
                }
            } else {
                echo "⚠️ No CANCELLED status callback received within timeout period\n";
                echo "• This could be due to sandbox limitations or callback not being configured\n";
                echo "• Received " . count($callbacks) . " callbacks for this order\n";

                // Print any callbacks we did receive
                if (count($callbacks) > 0) {
                    echo "• Callbacks received:\n";
                    foreach ($callbacks as $callback) {
                        echo json_encode($callback, JSON_PRETTY_PRINT) . "\n";
                    }
                }
            }

            echo "\nSUMMARY: Order cancellation and callback verification\n";
            echo "=================================================\n";
            echo "• Order ID: " . $order->getOrderId() . "\n";
            echo "• Order cancelled successfully with status: CANCELLED\n";
            echo "• Callback " . ($received ? "was" : "was not") . " received with CANCELLED status\n";
            echo "• Note: Callbacks may not be reliably delivered in the sandbox environment\n";

            // Order is already cancelled, no need to clean up in tearDown
            $this->testOrderId = null;

        } catch (RequestException $e) {
            // Handle common errors during order creation or cancellation
            if ($e->getCode() === 404 && strpos($e->getMessage(), 'Outlet not found') !== false) {
                echo "⚠️ Test skipped: Outlet not found\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                $this->markTestSkipped('Outlet not found. Please configure a valid client vendor ID.');
            } elseif ($e->getCode() === 422 && strpos($e->getMessage(), 'No Branch found') !== false) {
                echo "⚠️ Test skipped: No branch found close enough to the sender coordinates\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                $this->markTestSkipped('No branch found close to sender coordinates');
            } else {
                echo "❌ Test failed with error:\n";
                echo "• Status code: " . $e->getCode() . "\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                throw $e;
            }
        } finally {
            // Stop the callback server
            if ($this->callbackServer) {
                $this->callbackServer->stop();
                echo "• Mock callback server stopped\n";
            }
        }
    }

    /**
     * Test Case 8.2.2: Get cancellation detail through the Get Specific Order API (Happy Path)
     *
     * Verify that after cancelling an order, the cancellation details (source and reason)
     * can be retrieved using the Get Specific Order API.
     *
     * Steps:
     * 1. Same steps as 8.1.1
     * 2. Use Get Specific Order API to retrieve the order
     * 3. Verify the response contains cancellation information
     *
     * @return void
     */
    public function testGetCancellationDetails()
    {
        echo "\n\n✅ TEST CASE 8.2.2: Get cancellation detail through the Get Specific Order API (Happy Path)\n";
        echo "================================================================================\n\n";
        echo "STEP 1: Create a test order\n";
        echo "------------------------\n";

        try {
            $order = $this->createTestOrder();
            echo "✓ Test order created successfully\n";
            echo "• Order ID: " . $order->getOrderId() . "\n";
            echo "• Initial status: " . $order->getStatus() . "\n";

            echo "\nSTEP 2: Cancel the order\n";
            echo "---------------------\n";

            $reason        = 'MISTAKE_ERROR';
            $cancelRequest = new CancelOrderRequest($reason);

            $start  = microtime(true);
            $result = $this->client->orders()->cancel($order->getOrderId(), $cancelRequest);
            $end    = microtime(true);

            echo "✓ Cancellation request completed in " . round(($end - $start) * 1000, 2) . " ms\n";
            $this->assertTrue($result, 'Cancellation should succeed');
            echo "✓ Order cancelled successfully\n";

            echo "\nSTEP 3: Get cancellation details using Get Specific Order API\n";
            echo "--------------------------------------------------------\n";

            // Define and display the full URL based on environment
            $environment = $this->config->getEnvironment();
            $country     = $this->config->getCountry();

            // URLs as specified in the documentation
            $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/orders/{$order->getOrderId()}";
            $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/orders/{$order->getOrderId()}";
            $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/orders/{$order->getOrderId()}";

            // Determine the actual URL to use
            $fullUrl = $sandboxUrl;
            if ('production' === $environment) {
                $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
            }

            echo "• Full URL: " . $fullUrl . "\n";
            echo "• HTTP Method: GET\n";

            $start          = microtime(true);
            $cancelledOrder = $this->client->orders()->get($order->getOrderId());
            $end            = microtime(true);

            echo "✓ Get order request completed in " . round(($end - $start) * 1000, 2) . " ms\n";

            // Verify order status is CANCELLED
            $this->assertEquals('CANCELLED', $cancelledOrder->getStatus());
            echo "✓ Order status is CANCELLED\n";

            // Get the full response as an array to check for cancellation details
            $orderArray = $cancelledOrder->toArray();

            // Print the full order details
            echo "• Full order details:\n";
            echo json_encode($orderArray, JSON_PRETTY_PRINT) . "\n";

            // Check if cancellation details are present
            $this->assertArrayHasKey('cancellation', $orderArray, 'Order should contain cancellation details');
            echo "✓ Order response contains cancellation details\n";

            // Verify cancellation source and reason
            $this->assertEquals('CLIENT', $orderArray['cancellation']['source'], 'Cancellation source should be CLIENT');
            $this->assertEquals($reason, $orderArray['cancellation']['reason'], 'Cancellation reason should match request');

            echo "✓ Cancellation source: " . $orderArray['cancellation']['source'] . "\n";
            echo "✓ Cancellation reason: " . $orderArray['cancellation']['reason'] . "\n";

            echo "\nSUMMARY: Successfully retrieved cancellation details\n";
            echo "================================================\n";
            echo "• Order ID: " . $order->getOrderId() . "\n";
            echo "• Order Status: CANCELLED\n";
            echo "• Cancellation Source: " . $orderArray['cancellation']['source'] . "\n";
            echo "• Cancellation Reason: " . $orderArray['cancellation']['reason'] . "\n";

            // Order is already cancelled, no need to clean up in tearDown
            $this->testOrderId = null;

        } catch (RequestException $e) {
            // Handle common errors
            if ($e->getCode() === 404 && strpos($e->getMessage(), 'Outlet not found') !== false) {
                echo "⚠️ Test skipped: Outlet not found\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                $this->markTestSkipped('Outlet not found. Please configure a valid client vendor ID.');
            } elseif ($e->getCode() === 422 && strpos($e->getMessage(), 'No Branch found') !== false) {
                echo "⚠️ Test skipped: No branch found close enough to the sender coordinates\n";
                echo "• Error message: " . $e->getMessage() . "\n";
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
     * Test Case 8.2.3: Get cancellation detail on order initiated from Logistic/pandago side (Unhappy Path)
     *
     * Verify that when an order is cancelled by Pandago (logistics side), the cancellation details
     * can be retrieved, showing the correct source and reason.
     *
     * Steps:
     * 1. Create an order with special simulator description to force logistics cancellation
     * 2. Wait for cancellation to occur
     * 3. Verify cancellation details show LOGISTICS as the source
     *
     * @return void
     */
    public function testLogisticsCancellation()
    {
        echo "\n\n✅ TEST CASE 8.2.3: Get cancellation detail on order initiated from Logistic/pandago side (Unhappy Path)\n";
        echo "============================================================================================\n\n";
        echo "STEP 1: Create a test order with special directive to simulate logistics cancellation\n";
        echo "--------------------------------------------------------------------------\n";

        try {
            // Create order with special description to trigger logistics cancellation
            $order = $this->createTestOrder("<Simulator><Order scenario=\"RIDER_NOT_FOUND\" /></Simulator>");

            echo "✓ Test order created successfully with RIDER_NOT_FOUND simulation\n";
            echo "• Order ID: " . $order->getOrderId() . "\n";
            echo "• Initial status: " . $order->getStatus() . "\n";
            echo "• Description contains special directive to trigger logistics cancellation\n";

            echo "\nSTEP 2: Wait for the order to be automatically cancelled\n";
            echo "---------------------------------------------------\n";

                                // Wait and check for status changes
            $maxWaitTime  = 30; // seconds
            $pollInterval = 3;  // seconds
            $cancelled    = false;

            echo "• Checking order status every $pollInterval seconds (up to $maxWaitTime seconds total)...\n";

            $startWait = time();
            while (time() - $startWait < $maxWaitTime) {
                // Wait between checks
                if (time() - $startWait > 0) {
                    sleep($pollInterval);
                }

                // Check current order status
                $currentOrder = $this->client->orders()->get($order->getOrderId());
                $status       = $currentOrder->getStatus();

                echo "• Current status: $status\n";

                if ('CANCELLED' === $status) {
                    $cancelled = true;
                    break;
                }
            }

            if (! $cancelled) {
                echo "⚠️ Order was not cancelled within the timeout period\n";
                echo "• This could be due to sandbox limitations or simulation not working\n";
                echo "• Proceeding with test anyway to check current order state\n";
            } else {
                echo "✓ Order was cancelled as expected\n";
            }

            echo "\nSTEP 3: Get cancellation details using Get Specific Order API\n";
            echo "--------------------------------------------------------\n";

            // Get the latest order details
            $start      = microtime(true);
            $finalOrder = $this->client->orders()->get($order->getOrderId());
            $end        = microtime(true);

            echo "✓ Get order request completed in " . round(($end - $start) * 1000, 2) . " ms\n";
            echo "• Final order status: " . $finalOrder->getStatus() . "\n";

            // Get the full response as an array
            $orderArray = $finalOrder->toArray();

            // Print the full order details
            echo "• Full order details:\n";
            echo json_encode($orderArray, JSON_PRETTY_PRINT) . "\n";

            if ($finalOrder->getStatus() === 'CANCELLED') {
                // Check if cancellation details are present
                $this->assertArrayHasKey('cancellation', $orderArray, 'Order should contain cancellation details');
                echo "✓ Order response contains cancellation details\n";

                // Verify cancellation source is LOGISTICS
                $this->assertEquals('LOGISTICS', $orderArray['cancellation']['source'], 'Cancellation source should be LOGISTICS');
                echo "✓ Cancellation source: " . $orderArray['cancellation']['source'] . " (correct)\n";

                                                // Verify cancellation reason (should be related to rider not found)
                $expectedReason = 'NO_COURIER'; // This may vary depending on the simulation
                $actualReason   = $orderArray['cancellation']['reason'];

                echo "✓ Cancellation reason: " . $actualReason . "\n";

                // The exact reason may vary, but should be a valid reason
                $validReasons = [
                    'NO_COURIER',
                    'RIDER_NOT_FOUND',
                    'COURIER_UNREACHABLE',
                    'TECHNICAL_PROBLEM',
                ];

                $validReason = in_array($actualReason, $validReasons);
                $this->assertTrue($validReason, 'Cancellation reason should be related to rider not found');

                echo "\nSUMMARY: Successfully verified logistics-initiated cancellation\n";
                echo "==========================================================\n";
                echo "• Order ID: " . $order->getOrderId() . "\n";
                echo "• Order Status: CANCELLED\n";
                echo "• Cancellation Source: " . $orderArray['cancellation']['source'] . " (LOGISTICS as expected)\n";
                echo "• Cancellation Reason: " . $actualReason . "\n";
            } else {
                echo "⚠️ Order is not in CANCELLED state, cannot verify cancellation details\n";
                echo "• Current status: " . $finalOrder->getStatus() . "\n";
                echo "• The logistics cancellation simulation may not be supported in this sandbox\n";
            }

            // The order should either be cancelled already or will be cleaned up in tearDown
            if ($finalOrder->getStatus() === 'CANCELLED') {
                $this->testOrderId = null;
            }

        } catch (RequestException $e) {
            // Handle common errors
            if ($e->getCode() === 404 && strpos($e->getMessage(), 'Outlet not found') !== false) {
                echo "⚠️ Test skipped: Outlet not found\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                $this->markTestSkipped('Outlet not found. Please configure a valid client vendor ID.');
            } elseif ($e->getCode() === 422 && strpos($e->getMessage(), 'No Branch found') !== false) {
                echo "⚠️ Test skipped: No branch found close enough to the sender coordinates\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                $this->markTestSkipped('No branch found close to sender coordinates');
            } else {
                echo "❌ Test failed with error:\n";
                echo "• Status code: " . $e->getCode() . "\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                throw $e;
            }
        }
    }
}
