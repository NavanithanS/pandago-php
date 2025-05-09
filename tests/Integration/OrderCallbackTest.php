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
 * Test Cases for Order Status Updates via Callback
 *
 * 6.2.1: Get order status update using callback (Happy Path)
 * 6.2.2: Receive rider's ID after order accepted by foodpanda rider (Happy Path)
 *
 * Note: These tests simulate the callback scenario and require:
 * 1. A pre-configured callback URL registered with Pandago
 * 2. A webhook receiver/endpoint to accept callbacks
 * 3. The ability to create orders that will trigger callbacks
 */
class OrderCallbackTest extends TestCase
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
     * @var string|null
     */
    protected $callbackUrl;

    /**
     * @var array
     */
    protected $receivedCallbacks = [];

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

        // Get callback URL from config if available
        $this->callbackUrl = $this->getConfig()['callback_url'] ?? null;

        // If no callback URL is configured, skip the tests
        if (! $this->callbackUrl) {
            $this->markTestSkipped(
                'Callback tests require a callback URL to be configured in tests/config.php'
            );
        }
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
     * Test Case 6.2.1: Get order status update using callback (Happy Path)
     *
     * Provide callback URL to pandaGo team as part of Client Information when
     * you requested for pandaGo access. Any update in the order's status will
     * be notified to the callback url.
     *
     * Steps:
     * 1. Create new order
     * 2. PandaGo will send request to the callback url whenever there is a status update
     * 3. Order information from this request will be a simplified version to avoid revealing
     *    Personally Identifiable Information (PII)
     * 4. The full order information can be collected using the Get Specific Order API
     *
     * Note: Since this test is simulating callback behavior, it uses special techniques
     * to validate the callback functionality:
     * 1. Creates an order and logs its ID
     * 2. Demonstrates how to handle incoming webhooks
     * 3. Describes the expected callback payload structure
     *
     * @return void
     */
    public function testOrderStatusUpdateViaCallback()
    {
        echo "\n\n✅ TEST CASE 6.2.1: Get order status update using callback (Happy Path)\n";
        echo "=====================================================================\n\n";

        echo "STEP 1: Verify callback URL is properly configured\n";
        echo "-------------------------------------------------\n";
        echo "• Callback URL: " . $this->callbackUrl . "\n";
        echo "• Important: This URL must be registered with the Pandago team\n";
        echo "• The URL should point to an endpoint that can receive and process POST requests\n";

        echo "\nSTEP 2: Create a test order that will trigger callbacks\n";
        echo "-----------------------------------------------------\n";

        // Create test order
        $recipientLocation = new Location(
            '20 Esplanade Drive',
            1.2857488,
            103.8548608
        );
        $recipient = new Contact('Merlion', '+6500000000', $recipientLocation);

        $clientOrderId = 'test-callback-' . uniqid();
        $request       = new CreateOrderRequest(
            $recipient,
            23.50,
            'Callback Test Order'
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
            // Create the order to trigger callbacks
            $order             = $this->client->orders()->create($request);
            $this->testOrderId = $order->getOrderId();

            echo "✓ Test order created successfully\n";
            echo "• Order ID: " . $this->testOrderId . "\n";
            echo "• Initial status: " . $order->getStatus() . "\n";
            echo "• Client Order ID: " . $order->getClientOrderId() . "\n";

            echo "\nSTEP 3: Simulate callback handling process\n";
            echo "------------------------------------------\n";
            echo "• Note: The actual callbacks will be sent directly to your configured URL\n";
            echo "• Each status change will trigger a separate callback POST request\n";

            // Describe example callback payload
            $exampleCallbackPayload = [
                'order_id'        => $this->testOrderId,
                'client_order_id' => $clientOrderId,
                'status'          => 'NEW',
                // Other fields in callback will vary by status
            ];

            echo "• Example callback payload:\n";
            echo json_encode($exampleCallbackPayload, JSON_PRETTY_PRINT) . "\n";

            echo "\nSTEP 4: Demonstrate how to handle callbacks in your application\n";
            echo "-------------------------------------------------------------\n";

            echo "// Example webhook handler code:\n";
            echo "/**\n";
            echo " * A callback handler would typically look like this in your application:\n";
            echo " *\n";
            echo " * public function handlePandagoCallback(Request \$request)\n";
            echo " * {\n";
            echo " *     // Get the callback payload\n";
            echo " *     \$payload = \$request->json()->all();\n";
            echo " *     \n";
            echo " *     // Log the callback\n";
            echo " *     Log::info('Pandago callback received', \$payload);\n";
            echo " *     \n";
            echo " *     // Update your local order status\n";
            echo " *     \$orderId = \$payload['order_id'] ?? null;\n";
            echo " *     \$clientOrderId = \$payload['client_order_id'] ?? null;\n";
            echo " *     \$status = \$payload['status'] ?? null;\n";
            echo " *     \n";
            echo " *     if (\$orderId && \$status) {\n";
            echo " *         // Update order in your database\n";
            echo " *         // yourOrderService->updateStatus(\$orderId, \$status);\n";
            echo " *         \n";
            echo " *         // If the status is COURIER_ACCEPTED_DELIVERY, the driver info is available\n";
            echo " *         if (\$status === 'COURIER_ACCEPTED_DELIVERY' && isset(\$payload['driver']['id'])) {\n";
            echo " *             // Store the driver ID\n";
            echo " *             // yourOrderService->updateDriverInfo(\$orderId, \$payload['driver']);\n";
            echo " *             \n";
            echo " *             // Get full driver details if needed\n";
            echo " *             // \$fullOrderDetails = \$pandagoClient->orders()->get(\$orderId);\n";
            echo " *         }\n";
            echo " *     }\n";
            echo " *     \n";
            echo " *     // Return 200 OK to acknowledge receipt\n";
            echo " *     return response()->json(['success' => true]);\n";
            echo " * }\n";
            echo " */\n";

            echo "\nSTEP 5: Expected callback behavior and sequence\n";
            echo "----------------------------------------------\n";
            echo "• When this order progresses through the system, callbacks will be sent\n";
            echo "• Expected status transitions: NEW → RECEIVED → COURIER_ACCEPTED_DELIVERY → etc.\n";
            echo "• Each status change will trigger a separate callback to your configured URL\n";
            echo "• Callbacks contain minimal information to protect PII\n";
            echo "• Use the Get Specific Order API to fetch complete order details when needed\n";

            echo "\nSUMMARY: Order callback behavior and implementation\n";
            echo "==================================================\n";
            echo "• Order ID: " . $this->testOrderId . "\n";
            echo "• Callback URL: " . $this->callbackUrl . "\n";
            echo "• For each status change, Pandago will POST to your callback URL\n";
            echo "• Your webhook handler should process the callbacks and update your system\n";
            echo "• Use the test Order ID above to identify callbacks related to this test\n";

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

    /**
     * Test Case 6.2.2: Receive rider's ID after order accepted by foodpanda rider (Happy Path)
     *
     * Receive rider's id after order accepted by foodpanda rider. The rider id will
     * only be provided when rider have accepted the order, this will be reflected
     * with status COURIER_ACCEPTED_DELIVERY.
     *
     * Steps:
     * 1. Observe order created in 6.2.1
     * 2. Once order updated to COURIER_ACCEPTED_DELIVERY, the driver.id attribute
     *    will contain the rider id value
     * 3. In order to get personal information of the rider, please use the
     *    Get Specific Order API
     *
     * @return void
     */
    public function testReceiveRiderIdViaCallback()
    {
        echo "\n\n✅ TEST CASE 6.2.2: Receive rider's ID after order accepted by foodpanda rider (Happy Path)\n";
        echo "====================================================================================\n\n";

        echo "STEP 1: Create a test order and wait for courier acceptance\n";
        echo "-------------------------------------------------------\n";
        echo "• Note: This test focuses on the COURIER_ACCEPTED_DELIVERY status callback\n";
        echo "• The callback is triggered when a rider accepts the delivery\n";

        // Create test order
        $recipientLocation = new Location(
            '20 Esplanade Drive',
            1.2857488,
            103.8548608
        );
        $recipient = new Contact('Merlion', '+6500000000', $recipientLocation);

        $clientOrderId = 'test-courier-accept-' . uniqid();
        $request       = new CreateOrderRequest(
            $recipient,
            23.50,
            'Rider ID Test Order'
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
            // Create the order
            $order             = $this->client->orders()->create($request);
            $this->testOrderId = $order->getOrderId();

            echo "✓ Test order created successfully\n";
            echo "• Order ID: " . $this->testOrderId . "\n";
            echo "• Initial status: " . $order->getStatus() . "\n";

            echo "\nSTEP 2: Describe the COURIER_ACCEPTED_DELIVERY callback\n";
            echo "------------------------------------------------------\n";

            // Example callback payload when courier accepts delivery
            $exampleCourierAcceptedPayload = [
                'order_id'        => $this->testOrderId,
                'client_order_id' => $clientOrderId,
                'status'          => 'COURIER_ACCEPTED_DELIVERY',
                'driver'          => [
                    'id' => '12345', // Example driver ID
                ],
                'updated_at'      => time(),
            ];

            echo "• Example COURIER_ACCEPTED_DELIVERY callback payload:\n";
            echo json_encode($exampleCourierAcceptedPayload, JSON_PRETTY_PRINT) . "\n";

            echo "\nSTEP 3: Demonstrate processing the driver ID from callback\n";
            echo "---------------------------------------------------------\n";

            echo "// Example rider ID extraction code:\n";
            echo "/**\n";
            echo " * Extract and process rider ID from callback:\n";
            echo " *\n";
            echo " * public function handlePandagoCallback(Request \$request)\n";
            echo " * {\n";
            echo " *     \$payload = \$request->json()->all();\n";
            echo " *     \$status = \$payload['status'] ?? null;\n";
            echo " *     \n";
            echo " *     // Check if this is a courier acceptance callback\n";
            echo " *     if (\$status === 'COURIER_ACCEPTED_DELIVERY') {\n";
            echo " *         // Extract the rider ID\n";
            echo " *         \$riderId = \$payload['driver']['id'] ?? null;\n";
            echo " *         \n";
            echo " *         if (\$riderId) {\n";
            echo " *             Log::info('Rider assigned to order', [\n";
            echo " *                 'order_id' => \$payload['order_id'],\n";
            echo " *                 'rider_id' => \$riderId\n";
            echo " *             ]);\n";
            echo " *             \n";
            echo " *             // To get full rider details, use the Get Specific Order API\n";
            echo " *             \$pandagoClient = Client::fromArray(config('pandago'));\n";
            echo " *             \$fullOrderDetails = \$pandagoClient->orders()->get(\$payload['order_id']);\n";
            echo " *             \n";
            echo " *             // Full rider details are available in the driver property\n";
            echo " *             \$fullRiderDetails = \$fullOrderDetails->getDriver();\n";
            echo " *             \n";
            echo " *             // Update your local order with rider information\n";
            echo " *             // yourOrderService->updateRider(\$payload['order_id'], \$fullRiderDetails);\n";
            echo " *         }\n";
            echo " *     }\n";
            echo " *     \n";
            echo " *     return response()->json(['success' => true]);\n";
            echo " * }\n";
            echo " */\n";

            echo "\nSTEP 4: Demonstrate retrieving full rider details\n";
            echo "------------------------------------------------\n";

            // Poll for order status changes (simulating what would happen after callback)
            $hasDriver       = false;
            $attempts        = 3;
            $attemptInterval = 5; // seconds

            echo "• Polling for order status changes to check for driver assignment...\n";
            echo "• Note: In the sandbox environment, driver assignment may not occur quickly\n";

            for ($i = 1; $i <= $attempts; $i++) {
                echo "• Attempt $i of $attempts (waiting $attemptInterval seconds between checks)...\n";

                if ($i > 1) {
                    sleep($attemptInterval);
                }

                // Get current order status
                $currentOrder = $this->client->orders()->get($this->testOrderId);
                $status       = $currentOrder->getStatus();
                echo "  - Current status: $status\n";

                // Check if driver information is available
                $driver = $currentOrder->getDriver();
                if (isset($driver['id'])) {
                    $hasDriver = true;
                    echo "✓ Driver has been assigned!\n";
                    echo "• Driver ID: " . $driver['id'] . "\n";

                    if (isset($driver['name'])) {
                        echo "• Driver Name: " . $driver['name'] . "\n";
                    }

                    if (isset($driver['phone_number'])) {
                        echo "• Driver Phone: " . $driver['phone_number'] . "\n";
                    }

                    break;
                }
            }

            if (! $hasDriver) {
                echo "• No driver has been assigned yet (normal in sandbox environment)\n";

                // Show full example structure for demonstration purposes
                $exampleFullDriverDetails = [
                    'id'           => '12345',
                    'name'         => 'John Doe',
                    'phone_number' => '+6511111111',
                ];

                echo "• Example driver details from Get Specific Order API:\n";
                echo json_encode($exampleFullDriverDetails, JSON_PRETTY_PRINT) . "\n";
            }

            echo "\nSUMMARY: Rider ID callback handling\n";
            echo "=================================\n";
            echo "• Order ID: " . $this->testOrderId . "\n";
            echo "• Callback URL: " . $this->callbackUrl . "\n";
            echo "• When a rider accepts the order (COURIER_ACCEPTED_DELIVERY status):\n";
            echo "  1. A callback will be sent with the rider's ID\n";
            echo "  2. Use the Get Specific Order API to retrieve full rider details\n";
            echo "  3. Update your application with the rider information\n";
            echo "• This completes the rider assignment tracking process\n";

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

    /**
     * Create a mock callback handler to process callbacks.
     *
     * This is a utility method to simulate how a callback might be processed in a real application.
     * In a real implementation, this would be an HTTP endpoint that Pandago calls directly.
     *
     * @param array $payload The callback payload
     * @return array Response data
     */
    private function mockCallbackHandler(array $payload): array
    {
        // Log the received callback
        $this->receivedCallbacks[] = $payload;

        // Get key information from the payload
        $orderId       = $payload['order_id'] ?? null;
        $clientOrderId = $payload['client_order_id'] ?? null;
        $status        = $payload['status'] ?? null;

        // Process the callback based on status
        if ('COURIER_ACCEPTED_DELIVERY' === $status) {
            // Extract driver ID when courier accepts delivery
            $driverId = $payload['driver']['id'] ?? null;

            if ($driverId) {
                // In a real application, you would:
                // 1. Store the driver ID in your database
                // 2. Fetch full driver details if needed
                // 3. Notify relevant systems about the driver assignment

                // For test purposes, just log it
                echo "✓ Callback processed: Driver $driverId assigned to order $orderId\n";

                // You could fetch full order details:
                // $fullOrderDetails = $this->client->orders()->get($orderId);
            }
        } else {
            // Process other status updates
            echo "✓ Callback processed: Order $orderId status changed to $status\n";
        }

        // Return a success response (would be sent back to Pandago in a real implementation)
        return [
            'success' => true,
            'message' => "Callback for order $orderId processed successfully",
        ];
    }
}
