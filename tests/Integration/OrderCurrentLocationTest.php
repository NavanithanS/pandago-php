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
 * Test Cases for Order Current Location
 *
 * 7.1.1: Get the current location of an order (Happy Path)
 * 7.1.2: Get order current location before driver is assigned (Happy Path)
 * 7.1.3: Get order current location with wrong order ID (Unhappy Path)
 */
class OrderCurrentLocationTest extends TestCase
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
     * Test Case 7.1.1: Get the current location of an order (Happy Path)
     *
     * Using the order id value from Submit New Order API response,
     * get the current coordinate of an order.
     *
     * Steps:
     * 1. Create a new order to get an order ID
     * 2. Get the current location of the order
     * 3. Verify the response contains latitude, longitude, and updated_at fields
     *
     * @return void
     */
    public function testGetOrderCurrentLocation()
    {
        echo "\n\n✅ TEST CASE 7.1.1: Get the current location of an order (Happy Path)\n";
        echo "==================================================================\n\n";
        echo "STEP 1: Create a test order to get an order ID\n";
        echo "-------------------------------------------\n";

        // Create test order
        $recipientLocation = new Location(
            '20 Esplanade Drive',
            1.2857488,
            103.8548608
        );
        $recipient = new Contact('Merlion', '+6500000000', $recipientLocation);

        $clientOrderId = 'test-location-' . uniqid();
        $request       = new CreateOrderRequest(
            $recipient,
            23.50,
            'Location Test Order'
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
            // Create the order to get an order ID
            $order             = $this->client->orders()->create($request);
            $this->testOrderId = $order->getOrderId();

            echo "✓ Test order created successfully\n";
            echo "• Order ID: " . $this->testOrderId . "\n";
            echo "• Initial status: " . $order->getStatus() . "\n";

            echo "\nSTEP 2: Define API URL for getting order current location\n";
            echo "-----------------------------------------------------\n";

            // Define and display the full URL based on environment
            $environment = $this->config->getEnvironment();
            $country     = $this->config->getCountry();

            // URLs as specified in the documentation
            $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/orders/{$this->testOrderId}/coordinates";
            $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/orders/{$this->testOrderId}/coordinates";
            $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/orders/{$this->testOrderId}/coordinates";

            // Determine the actual URL to use
            $fullUrl = $sandboxUrl;
            if ('production' === $environment) {
                $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
            }

            echo "• Full URL: " . $fullUrl . "\n";
            echo "• HTTP Method: GET\n";
            echo "• Environment: " . $environment . "\n";
            echo "• Country: " . $country . "\n";

            echo "\nSTEP 3: Get the current location of the order\n";
            echo "-------------------------------------------\n";

            try {
                // Get the current location
                $start       = microtime(true);
                $coordinates = $this->client->orders()->getCoordinates($this->testOrderId);
                $end         = microtime(true);

                echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";
                echo "✓ Response status: 200 OK\n";
                echo "• Coordinates: " . $coordinates->getLatitude() . ", " . $coordinates->getLongitude() . "\n";
                echo "• Last updated: " . date('Y-m-d H:i:s', $coordinates->getUpdatedAt()) . "\n";

                // Verify the response properties
                $this->assertNotNull($coordinates->getLatitude(), 'Latitude should not be null');
                $this->assertNotNull($coordinates->getLongitude(), 'Longitude should not be null');
                $this->assertNotNull($coordinates->getUpdatedAt(), 'Updated at timestamp should not be null');

                echo "\nSUMMARY: Successfully retrieved order current location\n";
                echo "==================================================\n";
                echo "• API Endpoint: " . $fullUrl . "\n";
                echo "• Order ID: " . $this->testOrderId . "\n";
                echo "• Latitude: " . $coordinates->getLatitude() . "\n";
                echo "• Longitude: " . $coordinates->getLongitude() . "\n";
                echo "• Last updated: " . date('Y-m-d H:i:s', $coordinates->getUpdatedAt()) . "\n";

            } catch (RequestException $e) {
                // This could happen if no driver is assigned yet - we'll test this case specifically in testGetOrderLocationBeforeDriverAssigned
                echo "⚠️ Could not get coordinates: " . $e->getMessage() . "\n";
                echo "• Status code: " . $e->getCode() . "\n";

                if ($e->getCode() === 404 && strpos($e->getMessage(), 'no driver is currently assigned') !== false) {
                    echo "• This is expected if no driver has been assigned to the order yet.\n";
                    echo "• This specific case is covered in test case 7.1.2.\n";
                    $this->markTestSkipped('No driver assigned to the order yet. This is normal in sandbox.');
                } else {
                    throw $e;
                }
            }

        } catch (RequestException $e) {
            // Handle order creation errors
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
     * Test Case 7.1.2: Get order current location before driver is assigned (Happy Path)
     *
     * Send request for order current location before order status is updated to "NEAR_VENDOR"
     * OR before a rider has accepted the order.
     *
     * Steps:
     * 1. Create a new order to get an order ID
     * 2. Immediately try to get the current location (before driver assignment)
     * 3. Verify the response is a 404 error with appropriate message
     *
     * @return void
     */
    public function testGetOrderLocationBeforeDriverAssigned()
    {
        echo "\n\n✅ TEST CASE 7.1.2: Get order current location before driver is assigned (Happy Path)\n";
        echo "==============================================================================\n\n";
        echo "STEP 1: Create a test order to get an order ID\n";
        echo "-------------------------------------------\n";

        // Create test order (same setup as before)
        $recipientLocation = new Location(
            '20 Esplanade Drive',
            1.2857488,
            103.8548608
        );
        $recipient = new Contact('Merlion', '+6500000000', $recipientLocation);

        $clientOrderId = 'test-no-driver-' . uniqid();
        $request       = new CreateOrderRequest(
            $recipient,
            23.50,
            'No Driver Test Order'
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
            // Create the order to get an order ID
            $order             = $this->client->orders()->create($request);
            $this->testOrderId = $order->getOrderId();

            echo "✓ Test order created successfully\n";
            echo "• Order ID: " . $this->testOrderId . "\n";
            echo "• Initial status: " . $order->getStatus() . "\n";

            echo "\nSTEP 2: Define API URL for getting order current location\n";
            echo "-----------------------------------------------------\n";

            // Define and display the full URL based on environment
            $environment = $this->config->getEnvironment();
            $country     = $this->config->getCountry();

            // URLs as specified in the documentation
            $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/orders/{$this->testOrderId}/coordinates";
            $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/orders/{$this->testOrderId}/coordinates";
            $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/orders/{$this->testOrderId}/coordinates";

            // Determine the actual URL to use
            $fullUrl = $sandboxUrl;
            if ('production' === $environment) {
                $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
            }

            echo "• Full URL: " . $fullUrl . "\n";
            echo "• HTTP Method: GET\n";
            echo "• Environment: " . $environment . "\n";
            echo "• Country: " . $country . "\n";

            echo "\nSTEP 3: Try to get the current location before driver assignment\n";
            echo "-----------------------------------------------------------\n";
            echo "• In a newly created order, no driver should be assigned yet\n";
            echo "• We expect a 404 error with the message 'no driver is currently assigned to this order'\n";

            try {
                // Try to get the current location immediately (before driver assignment)
                $start       = microtime(true);
                $coordinates = $this->client->orders()->getCoordinates($this->testOrderId);
                $end         = microtime(true);

                // If we get here, the test has failed because we expected an exception
                echo "⚠️ Test failed: Expected exception was not thrown\n";
                echo "• Received coordinates: " . $coordinates->getLatitude() . ", " . $coordinates->getLongitude() . "\n";
                $this->fail('Expected 404 exception but got successful response');

            } catch (RequestException $e) {
                $end = microtime(true);
                echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";
                echo "✓ Received expected error response\n";

                // Verify the error is a 404 with the expected message
                $this->assertEquals(404, $e->getCode(), 'Error code should be 404');
                $this->assertStringContainsString('no driver is currently assigned', $e->getMessage(), 'Error message should indicate no driver is assigned');

                echo "✓ Status code: 404 Not Found\n";
                echo "✓ Error message: " . $e->getMessage() . "\n";

                echo "\nSUMMARY: Successfully verified no driver assigned behavior\n";
                echo "======================================================\n";
                echo "• API Endpoint: " . $fullUrl . "\n";
                echo "• Order ID: " . $this->testOrderId . "\n";
                echo "• No coordinates available as expected because no driver is assigned\n";
            }

        } catch (RequestException $e) {
            // Handle order creation errors
            if ($e->getCode() === 404 && strpos($e->getMessage(), 'Outlet not found') !== false) {
                echo "⚠️ Test skipped: Outlet not found\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                $this->markTestSkipped('Outlet not found. Please configure a valid client vendor ID.');
            } elseif ($e->getCode() === 422 && strpos($e->getMessage(), 'No Branch found') !== false) {
                echo "⚠️ Test skipped: No branch found close enough to the sender coordinates\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                $this->markTestSkipped('No branch found close to sender coordinates');
            } else {
                echo "❌ Test failed with error during order creation:\n";
                echo "• Status code: " . $e->getCode() . "\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                throw $e;
            }
        }
    }

    /**
     * Test Case 7.1.3: Get order current location with wrong order ID (Unhappy Path)
     *
     * Send request using the wrong order id and verify error handling.
     *
     * Steps:
     * 1. Use an invalid/non-existent order ID
     * 2. Try to get the current location
     * 3. Verify the response is a 404 error with "Order not found" message
     *
     * @return void
     */
    public function testGetOrderLocationWithWrongId()
    {
        echo "\n\n✅ TEST CASE 7.1.3: Get order current location with wrong order ID (Unhappy Path)\n";
        echo "==========================================================================\n\n";
        echo "STEP 1: Define an invalid order ID\n";
        echo "--------------------------------\n";

        // Generate a random invalid order ID
        $invalidOrderId = 'invalid-' . uniqid();
        echo "• Invalid order ID: " . $invalidOrderId . "\n";

        echo "\nSTEP 2: Define API URL for getting order current location\n";
        echo "-----------------------------------------------------\n";

        // Define and display the full URL based on environment
        $environment = $this->config->getEnvironment();
        $country     = $this->config->getCountry();

        // URLs as specified in the documentation
        $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/orders/{$invalidOrderId}/coordinates";
        $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/orders/{$invalidOrderId}/coordinates";
        $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/orders/{$invalidOrderId}/coordinates";

        // Determine the actual URL to use
        $fullUrl = $sandboxUrl;
        if ('production' === $environment) {
            $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
        }

        echo "• Full URL: " . $fullUrl . "\n";
        echo "• HTTP Method: GET\n";
        echo "• Environment: " . $environment . "\n";
        echo "• Country: " . $country . "\n";

        echo "\nSTEP 3: Try to get the current location using an invalid order ID\n";
        echo "------------------------------------------------------------\n";
        echo "• We expect a 404 error with the message 'Order not found'\n";

        try {
            // Try to get the current location with an invalid order ID
            $start       = microtime(true);
            $coordinates = $this->client->orders()->getCoordinates($invalidOrderId);
            $end         = microtime(true);

            // If we get here, the test has failed because we expected an exception
            echo "⚠️ Test failed: Expected exception was not thrown\n";
            echo "• Received coordinates: " . $coordinates->getLatitude() . ", " . $coordinates->getLongitude() . "\n";
            $this->fail('Expected 404 exception but got successful response');

        } catch (RequestException $e) {
            $end = microtime(true);
            echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";
            echo "✓ Received expected error response\n";

            // Verify the error is a 404 with the expected message
            $this->assertEquals(404, $e->getCode(), 'Error code should be 404');
            $this->assertStringContainsString('Order not found', $e->getMessage(), 'Error message should indicate order not found');

            echo "✓ Status code: 404 Not Found\n";
            echo "✓ Error message: " . $e->getMessage() . "\n";

            echo "\nSUMMARY: Successfully verified invalid order ID behavior\n";
            echo "====================================================\n";
            echo "• API Endpoint: " . $fullUrl . "\n";
            echo "• Invalid Order ID: " . $invalidOrderId . "\n";
            echo "• Properly returned 404 Not Found with 'Order not found' message\n";
        }
    }
}
