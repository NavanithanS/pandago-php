<?php
namespace Nava\Pandago\Tests\Integration;

use Nava\Pandago\Client;
use Nava\Pandago\Config;
use Nava\Pandago\Exceptions\RequestException;
use Nava\Pandago\Models\Location;
use Nava\Pandago\Models\Order\CreateOrderRequest;
use Nava\Pandago\Tests\Helpers\TestAddresses;
use Nava\Pandago\Tests\TestCase;

/**
 * Test Cases for delivery time estimation
 *
 * 4.1.1: Get delivery estimate time before committing to an order (Happy Path)
 */
class OrderTimeEstimationTest extends TestCase
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
     * Test Case 4.1.1: Get delivery estimate time before committing to an order (Happy Path)
     *
     * Sends order details JSON to estimate the delivery time from sender's location to recipient's address.
     * Ensures recipient's address is within pandago delivery area.
     *
     * Steps:
     * 1. [POST] Request to /orders/time endpoint
     * 2. Use authorization token
     * 3. Include sender attribute in the request body
     * 4. Expect 200 OK response
     * 5. Verify response contains delivery time estimates
     *
     * @return void
     */
    public function testEstimateDeliveryTime()
    {
        echo "\n\n✅ TEST CASE 4.1.1: Get delivery estimate time before committing to an order (Happy Path)\n";
        echo "=================================================================================\n\n";
        echo "STEP 1: Prepare order request with sender and recipient information\n";
        echo "----------------------------------------------------------------\n";

        // Create recipient with location in Singapore
        $recipient = TestAddresses::getCustomerContact();
        echo "• Recipient created with location at coordinates: 1.303166607308108, 103.83618242858377\n";

        // Create order request with a client order ID for tracing
        $clientOrderId = 'test-time-' . uniqid();
        $request       = new CreateOrderRequest(
            $recipient,
            23.50,             // Amount
            'Refreshing drink' // Description
        );
        $request->setClientOrderId($clientOrderId);
        echo "• Order request created with amount: 23.50\n";
        echo "• Client order ID: " . $clientOrderId . "\n";

        // Set sender information - this is required for time estimation
        $sender = TestAddresses::getOutletContact();
        $request->setSender($sender);
        echo "• Sender information added with location at coordinates: 1.3018914131301271, 103.83548392113393\n";

        // Display the request payload
        $requestPayload = $request->toArray();
        echo "\n• Request Payload (JSON):\n";
        echo json_encode($requestPayload, JSON_PRETTY_PRINT) . "\n";

        // Define and display the full URL based on environment
        $environment = $this->config->getEnvironment();
        $country     = $this->config->getCountry();

        // URLs as specified in the documentation
        $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/orders/time";
        $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/orders/time";
        $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/orders/time";

        // Determine the actual URL to use
        $fullUrl = $sandboxUrl;
        if ('production' === $environment) {
            $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
        }

        echo "\nSTEP 2: Call the time estimation endpoint\n";
        echo "-------------------------------------\n";
        echo "• Full URL: " . $fullUrl . "\n";
        echo "• HTTP Method: POST\n";
        echo "• Environment: " . $environment . "\n";
        echo "• Country: " . $country . "\n";

        // For reference, also show the URL constructed from the Config object
        $configUrl = $this->config->getApiBaseUrl() . '/orders/time';
        echo "• URL from Config: " . $configUrl . "\n";

        try {
            // Request time estimation
            $start  = microtime(true);
            $result = $this->client->orders()->estimateTime($request);
            $end    = microtime(true);

            echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";
            echo "✓ Response status: 200 OK\n";

            echo "\nSTEP 3: Examine the response\n";
            echo "--------------------------\n";
            echo "• Response JSON:\n";
            echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

            echo "\nSTEP 4: Verify the response contains delivery time estimates\n";
            echo "-----------------------------------------------------\n";

            // Verify the response structure
            $this->assertIsArray($result);
            echo "✓ Response is a valid array\n";

            // Response should contain estimated_pickup_time
            $this->assertArrayHasKey('estimated_pickup_time', $result);
            echo "✓ Response contains 'estimated_pickup_time' field\n";

            // Response should contain estimated_delivery_time
            $this->assertArrayHasKey('estimated_delivery_time', $result);
            echo "✓ Response contains 'estimated_delivery_time' field\n";

            // Validate the time format (ISO 8601)
            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?Z$/',
                $result['estimated_pickup_time']
            );
            echo "✓ Pickup time is in ISO 8601 format: " . $result['estimated_pickup_time'] . "\n";

            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?Z$/',
                $result['estimated_delivery_time']
            );
            echo "✓ Delivery time is in ISO 8601 format: " . $result['estimated_delivery_time'] . "\n";

            // Parse times to check if delivery is after pickup
            $pickupTime   = strtotime($result['estimated_pickup_time']);
            $deliveryTime = strtotime($result['estimated_delivery_time']);
            $this->assertGreaterThan($pickupTime, $deliveryTime, 'Delivery time should be after pickup time');

            $timeDifference = $deliveryTime - $pickupTime;
            echo "✓ Delivery time is after pickup time by " . $timeDifference . " seconds (" .
            round($timeDifference / 60, 1) . " minutes)\n";

            // Check if client order ID is included in response
            if (isset($result['client_order_id'])) {
                echo "✓ Response includes client_order_id: " . $result['client_order_id'] . "\n";
                // Verify it matches what we sent
                $this->assertEquals($clientOrderId, $result['client_order_id']);
                echo "✓ Returned client_order_id matches our request\n";
            }

            // Output any additional fields returned
            foreach ($result as $key => $value) {
                if ('estimated_pickup_time' !== $key && 'estimated_delivery_time' !== $key && 'client_order_id' !== $key) {
                    echo "• Additional field in response: $key: " . (is_scalar($value) ? $value : json_encode($value)) . "\n";
                }
            }

            // Calculate distance between sender and recipient (in kilometers)
            $distance = $this->calculateDistance(
                $sender->getLocation()->getLatitude(),
                $sender->getLocation()->getLongitude(),
                $recipient->getLocation()->getLatitude(),
                $recipient->getLocation()->getLongitude()
            );
            echo "• Calculated distance between sender and recipient: " . round($distance, 2) . " km\n";

            echo "\nSUMMARY: Successfully estimated delivery time\n";
            echo "==========================================\n";
            echo "• API Endpoint: " . $fullUrl . "\n";
            echo "• Sender location: " . $sender->getLocation()->getAddress() . "\n";
            echo "• Recipient location: " . $recipient->getLocation()->getAddress() . "\n";
            echo "• Distance: " . round($distance, 2) . " km\n";
            echo "• Estimated pickup time: " . $result['estimated_pickup_time'] . "\n";
            echo "• Estimated delivery time: " . $result['estimated_delivery_time'] . "\n";
            echo "• Estimated delivery duration: " . round($timeDifference / 60, 1) . " minutes\n";

        } catch (RequestException $e) {
            // For common errors with time estimation, provide more contextual information
            if ($e->getCode() === 422 && strpos($e->getMessage(), 'No Branch found') !== false) {
                echo "⚠️ Test skipped: No branch found close enough to the sender coordinates\n";
                echo "• API Endpoint: " . $fullUrl . "\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                echo "• Request payload: " . json_encode($requestPayload, JSON_PRETTY_PRINT) . "\n";
                echo "• Consider using coordinates that are within a delivery area\n";
                $this->markTestSkipped('No branch found close to sender coordinates');
            } elseif ($e->getCode() === 422 && strpos($e->getMessage(), 'Coordinates out of bounds') !== false) {
                echo "⚠️ Test skipped: Coordinates are outside the delivery area\n";
                echo "• API Endpoint: " . $fullUrl . "\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                $this->markTestSkipped('Coordinates are outside delivery area');
            } else {
                echo "❌ Test failed with error:\n";
                echo "• API Endpoint: " . $fullUrl . "\n";
                echo "• Status code: " . $e->getCode() . "\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                echo "• Request payload: " . json_encode($requestPayload, JSON_PRETTY_PRINT) . "\n";
                if ($e->getData()) {
                    echo "• Response data: " . json_encode($e->getData(), JSON_PRETTY_PRINT) . "\n";
                }
                throw $e;
            }
        }
    }

    /**
     * Test Case 4.1.2: Get delivery estimate time with address outside delivery area (Unhappy Path)
     *
     * Use recipient's address that is outside the agreed outlet delivery area.
     * For Brands with Outlet configuration only.
     *
     * Steps:
     * 1. Same steps as 4.1.1
     * 2. For body change the recipient's longitude and latitude out of delivery area
     * 3. Response expected: 422 Unprocessable Entity with message "Unable to process order\norder is outside deliverable range"
     *
     * @return void
     */
    public function testEstimateDeliveryTimeOutsideArea()
    {
        echo "\n\n✅ TEST CASE 4.1.2: Get delivery estimate time with address outside delivery area (Unhappy Path)\n";
        echo "==========================================================================================\n\n";
        echo "STEP 1: Prepare order request with sender and out-of-range recipient information\n";
        echo "------------------------------------------------------------------------\n";

        $recipient = TestAddresses::getOutOfRangeContact();
        echo "• Recipient created with out-of-range location at coordinates\n";

        // Create order request with a client order ID for tracing
        $clientOrderId = 'test-out-of-range-' . uniqid();
        $request       = new CreateOrderRequest(
            $recipient,
            25.00,
            'Test Order Outside Delivery Area'
        );
        $request->setClientOrderId($clientOrderId);
        echo "• Order request created with amount: 25.00\n";
        echo "• Client order ID: " . $clientOrderId . "\n";

        // Set sender with a valid location in Singapore
        $sender = TestAddresses::getOutletContact();
        $request->setSender($sender);
        echo "• Sender information added with valid location at coordinates: 1.3018914131301271, 103.83548392113393\n";

        // Display the request payload
        $requestPayload = $request->toArray();
        echo "\n• Request Payload (JSON):\n";
        echo json_encode($requestPayload, JSON_PRETTY_PRINT) . "\n";

        // Define and display the full URL based on environment
        $environment = $this->config->getEnvironment();
        $country     = $this->config->getCountry();

        // URLs as specified in the documentation
        $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/orders/time";
        $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/orders/time";
        $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/orders/time";

        // Determine the actual URL to use
        $fullUrl = $sandboxUrl;
        if ('production' === $environment) {
            $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
        }

        echo "\nSTEP 2: Call the time estimation endpoint with out-of-range address\n";
        echo "---------------------------------------------------------------\n";
        echo "• Full URL: " . $fullUrl . "\n";
        echo "• HTTP Method: POST\n";
        echo "• Environment: " . $environment . "\n";
        echo "• Country: " . $country . "\n";
        echo "• Sending request with out-of-range coordinates\n";

        try {
            // Request time estimation
            $start  = microtime(true);
            $result = $this->client->orders()->estimateTime($request);
            $end    = microtime(true);

            // If we get here without an exception, the test has failed
            echo "❌ Test failed: Expected RequestException was not thrown\n";
            echo "• Response received: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
            $this->fail('Expected RequestException for out-of-range address was not thrown');
        } catch (RequestException $e) {
            $end = microtime(true);
            echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";

            echo "\nSTEP 3: Verify the error response indicates address is outside delivery area\n";
            echo "----------------------------------------------------------------------\n";

            // Verify the status code is 422 Unprocessable Entity
            $this->assertEquals(
                422,
                $e->getCode(),
                'Expected HTTP 422 Unprocessable Entity status code'
            );
            echo "✓ Response status: 422 Unprocessable Entity - Correct error code for out-of-range address\n";

            // Verify the error message contains the expected text
            $expectedErrorMessage = "Unable to process order\norder is outside deliverable range";
            $this->assertStringContainsString(
                'outside deliverable range',
                $e->getMessage(),
                'Error message should indicate the order is outside deliverable range'
            );
            echo "✓ Error message correctly indicates the order is outside deliverable range\n";

            // Print details about the error for debugging
            echo "• Complete error message: " . $e->getMessage() . "\n";

            if ($e->getData()) {
                echo "• Error data: " . json_encode($e->getData(), JSON_PRETTY_PRINT) . "\n";
            }

            echo "\nSUMMARY: Successfully validated error for out-of-range address\n";
            echo "=========================================================\n";
            echo "• API Endpoint: " . $fullUrl . "\n";
            echo "• Correctly received error status code: " . $e->getCode() . "\n";
            echo "• API properly rejected the request with 'outside deliverable range' error\n";
            echo "• NOTE: This test confirms that for brands with outlet configuration,\n";
            echo "  addresses outside the delivery area are properly rejected.\n";
        }
    }

    /**
     * Test Case 4.1.3: Get delivery estimate time without sender (Unhappy Path)
     *
     * Sends order details JSON to estimate the delivery time but excludes the sender attribute.
     * For brands without outlet configuration, this will still generate an estimate but it may be
     * inaccurate as the system will use the vendor's default location.
     *
     * Steps:
     * 1. [POST] Request to /orders/time endpoint
     * 2. Use authorization token
     * 3. Exclude sender attribute in the request body
     * 4. Expect 200 OK response
     * 5. Note that estimates may be inaccurate due to missing sender information
     *
     * @return void
     */
    public function testEstimateDeliveryTimeWithoutSender()
    {
        echo "\n\n✅ TEST CASE 4.1.3: Get delivery estimate time without sender (Unhappy Path)\n";
        echo "===========================================================================\n\n";
        echo "STEP 1: Prepare order request with recipient information but without sender\n";
        echo "----------------------------------------------------------------------\n";

        // Create recipient with location in Singapore
        $recipient = TestAddresses::getCustomerContact();
        echo "• Recipient created with location at coordinates: 1.303166607308108, 103.83618242858377\n";

        // Create order request with a client order ID for tracing
        $clientOrderId = 'test-time-no-sender-' . uniqid();
        $request       = new CreateOrderRequest(
            $recipient,
            23.50,             // Amount
            'Refreshing drink' // Description
        );
        $request->setClientOrderId($clientOrderId);
        echo "• Order request created with amount: 23.50\n";
        echo "• Client order ID: " . $clientOrderId . "\n";
        echo "• NOTE: Sender information is intentionally NOT provided for this test case\n";

        // Display the request payload
        $requestPayload = $request->toArray();
        echo "\n• Request Payload (JSON):\n";
        echo json_encode($requestPayload, JSON_PRETTY_PRINT) . "\n";

        // Define and display the full URL based on environment
        $environment = $this->config->getEnvironment();
        $country     = $this->config->getCountry();

        // URLs as specified in the documentation
        $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/orders/time";
        $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/orders/time";
        $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/orders/time";

        // Determine the actual URL to use
        $fullUrl = $sandboxUrl;
        if ('production' === $environment) {
            $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
        }

        echo "\nSTEP 2: Call the time estimation endpoint without sender information\n";
        echo "----------------------------------------------------------------\n";
        echo "• Full URL: " . $fullUrl . "\n";
        echo "• HTTP Method: POST\n";
        echo "• Environment: " . $environment . "\n";
        echo "• Country: " . $country . "\n";

        try {
            // Request time estimation without sender
            $start  = microtime(true);
            $result = $this->client->orders()->estimateTime($request);
            $end    = microtime(true);

            echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";
            echo "✓ Response status: 200 OK\n";

            echo "\nSTEP 3: Examine the response\n";
            echo "--------------------------\n";
            echo "• Response JSON:\n";
            echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

            echo "\nSTEP 4: Verify response contains time estimates (potentially inaccurate)\n";
            echo "-------------------------------------------------------------------\n";

            // Verify the response structure
            $this->assertIsArray($result);
            echo "✓ Response is a valid array\n";

            // Response should contain estimated_pickup_time
            $this->assertArrayHasKey('estimated_pickup_time', $result);
            echo "✓ Response contains 'estimated_pickup_time' field\n";

            // Response should contain estimated_delivery_time
            $this->assertArrayHasKey('estimated_delivery_time', $result);
            echo "✓ Response contains 'estimated_delivery_time' field\n";

            // Validate the time format (ISO 8601)
            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?Z$/',
                $result['estimated_pickup_time']
            );
            echo "✓ Pickup time is in ISO 8601 format: " . $result['estimated_pickup_time'] . "\n";

            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?Z$/',
                $result['estimated_delivery_time']
            );
            echo "✓ Delivery time is in ISO 8601 format: " . $result['estimated_delivery_time'] . "\n";

            // Parse times to check if delivery is after pickup
            $pickupTime   = strtotime($result['estimated_pickup_time']);
            $deliveryTime = strtotime($result['estimated_delivery_time']);
            $this->assertGreaterThan($pickupTime, $deliveryTime, 'Delivery time should be after pickup time');

            $timeDifference = $deliveryTime - $pickupTime;
            echo "✓ Delivery time is after pickup time by " . $timeDifference . " seconds (" .
            round($timeDifference / 60, 1) . " minutes)\n";

            // Check if client order ID is included in response
            if (isset($result['client_order_id'])) {
                echo "✓ Response includes client_order_id: " . $result['client_order_id'] . "\n";
                // Verify it matches what we sent
                $this->assertEquals($clientOrderId, $result['client_order_id']);
                echo "✓ Returned client_order_id matches our request\n";
            }

            echo "\nSTEP 5: Document potential inaccuracy due to missing sender attribute\n";
            echo "-------------------------------------------------------------------\n";
            echo "⚠️ NOTE: For brands without outlet configuration, the time estimates may be inaccurate\n";
            echo "⚠️ because the system is using the vendor's default location instead of a specific sender location.\n";
            echo "⚠️ This is expected behavior for this unhappy path test case.\n";

            echo "\nSUMMARY: Successfully received time estimates (but potentially inaccurate)\n";
            echo "===================================================================\n";
            echo "• API Endpoint: " . $fullUrl . "\n";
            echo "• Sender information: NOT PROVIDED (Using vendor's default location)\n";
            echo "• Recipient location: " . $recipient->getLocation()->getAddress() . "\n";
            echo "• Estimated pickup time: " . $result['estimated_pickup_time'] . "\n";
            echo "• Estimated delivery time: " . $result['estimated_delivery_time'] . "\n";
            echo "• Estimated delivery duration: " . round($timeDifference / 60, 1) . " minutes\n";
            echo "• IMPORTANT: These estimates may be inaccurate due to missing sender information\n";

        } catch (RequestException $e) {
            if ($e->getCode() === 422 && strpos($e->getMessage(), 'sender is required') !== false) {
                echo "ℹ️ Test result: The API requires sender information and rejected the request\n";
                echo "• This is an acceptable outcome for brands WITH outlet configuration\n";
                echo "• For brands WITHOUT outlet configuration, the API should accept the request but provide potentially inaccurate estimates\n";
                echo "• API Endpoint: " . $fullUrl . "\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                $this->markTestSkipped('API requires sender information for this configuration');
            } elseif ($e->getCode() === 422 && strpos($e->getMessage(), 'No Branch found') !== false) {
                echo "⚠️ Test skipped: No branch found close enough to use as default sender\n";
                echo "• API Endpoint: " . $fullUrl . "\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                $this->markTestSkipped('No branch found to use as default sender');
            } else {
                echo "❌ Test failed with error:\n";
                echo "• API Endpoint: " . $fullUrl . "\n";
                echo "• Status code: " . $e->getCode() . "\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                echo "• Request payload: " . json_encode($requestPayload, JSON_PRETTY_PRINT) . "\n";
                if ($e->getData()) {
                    echo "• Response data: " . json_encode($e->getData(), JSON_PRETTY_PRINT) . "\n";
                }
                throw $e;
            }
        }
    }

    /**
     * Calculate the distance between two points using the Haversine formula.
     *
     * @param float $lat1 Latitude of point 1
     * @param float $lon1 Longitude of point 1
     * @param float $lat2 Latitude of point 2
     * @param float $lon2 Longitude of point 2
     * @return float Distance in kilometers
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radius of the Earth in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon / 2) * sin($dLon / 2);

        $c        = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }
}
