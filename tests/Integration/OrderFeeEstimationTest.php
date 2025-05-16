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
 * Test Cases for delivery fee estimation
 *
 * 3.1.1: Get delivery fee estimate before commiting to an order (Happy Path)
 * 3.1.2: Get delivery fee estimate with address outside delivery area (Unhappy Path)
 */
class OrderFeeEstimationTest extends TestCase
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
     * Test Case 3.1.1: Get delivery fee estimate before commiting to an order (Happy Path)
     *
     * Sends order details JSON to estimate the delivery fee from sender's location to recipient's address.
     * Ensures recipient's address is within pandago delivery area.
     *
     * Steps:
     * 1. [POST] Request to /orders/fee endpoint
     * 2. Use authorization token
     * 3. Include sender attribute in the request body
     * 4. Expect 200 OK response
     * 5. Verify response contains delivery estimates
     *
     * @return void
     */
    public function testEstimateDeliveryFee()
    {
        echo "\n\n✅ TEST CASE 3.1.1: Get delivery fee estimate before commiting to an order (Happy Path)\n";
        echo "=================================================================================\n\n";
        echo "STEP 1: Prepare order request with sender and recipient information\n";
        echo "----------------------------------------------------------------\n";

        // Create recipient with location in Singapore
        $recipient = TestAddresses::getCustomerContact();
        echo "• Recipient created with location at coordinates: 1.303166607308108, 103.83618242858377\n";

        // Create order request with a client order ID for tracing
        $clientOrderId = 'test-fee-' . uniqid();
        $request       = new CreateOrderRequest(
            $recipient,
            23.50,             // Amount
            'Refreshing drink' // Description
        );
        $request->setClientOrderId($clientOrderId);
        echo "• Order request created with amount: 23.50\n";
        echo "• Client order ID: " . $clientOrderId . "\n";

        // Set sender information - this is required for fee estimation
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
        $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/orders/fee";
        $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/orders/fee";
        $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/orders/fee";

        // Determine the actual URL to use
        $fullUrl = $sandboxUrl;
        if ('production' === $environment) {
            $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
        }

        echo "\nSTEP 2: Call the fee estimation endpoint\n";
        echo "-------------------------------------\n";
        echo "• Full URL: " . $fullUrl . "\n";
        echo "• HTTP Method: POST\n";
        echo "• Environment: " . $environment . "\n";
        echo "• Country: " . $country . "\n";

        // For reference, also show the URL constructed from the Config object
        $configUrl = $this->config->getApiBaseUrl() . '/orders/fee';
        echo "• URL from Config: " . $configUrl . "\n";

        try {
            // Request fee estimation
            $start  = microtime(true);
            $result = $this->client->orders()->estimateFee($request);
            $end    = microtime(true);

            echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";
            echo "✓ Response status: 200 OK\n";

            echo "\nSTEP 3: Examine the response\n";
            echo "--------------------------\n";
            echo "• Response JSON:\n";
            echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

            echo "\nSTEP 4: Verify the response contains delivery fee estimate\n";
            echo "-----------------------------------------------------\n";

            // Verify the response structure
            $this->assertIsArray($result);
            echo "✓ Response is a valid array\n";

            // Response should contain estimated_delivery_fee
            $this->assertArrayHasKey('estimated_delivery_fee', $result);
            echo "✓ Response contains 'estimated_delivery_fee' field\n";

            // Fee should be a positive number
            $this->assertIsNumeric($result['estimated_delivery_fee']);
            $this->assertGreaterThan(0, $result['estimated_delivery_fee']);
            echo "✓ Estimated delivery fee is a positive number: " . $result['estimated_delivery_fee'] . "\n";

            // Check if client order ID is included in response
            if (isset($result['client_order_id'])) {
                echo "✓ Response includes client_order_id: " . $result['client_order_id'] . "\n";
                // Verify it matches what we sent
                $this->assertEquals($clientOrderId, $result['client_order_id']);
                echo "✓ Returned client_order_id matches our request\n";
            }

            // Output any additional fields returned
            foreach ($result as $key => $value) {
                if ('estimated_delivery_fee' !== $key && 'client_order_id' !== $key) {
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
            echo "• Estimated cost per km: " . round($result['estimated_delivery_fee'] / $distance, 2) . "\n";

            echo "\nSUMMARY: Successfully estimated delivery fee\n";
            echo "==========================================\n";
            echo "• API Endpoint: " . $fullUrl . "\n";
            echo "• Sender location: " . $sender->getLocation()->getAddress() . "\n";
            echo "• Recipient location: " . $recipient->getLocation()->getAddress() . "\n";
            echo "• Distance: " . round($distance, 2) . " km\n";
            echo "• Estimated delivery fee: " . $result['estimated_delivery_fee'] . "\n";

        } catch (RequestException $e) {
            // For common errors with fee estimation, provide more contextual information
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
     * Test Case 3.1.2: Get delivery fee estimate with address outside delivery area (Unhappy Path)
     *
     * Use recipient's address that is out of the agreed outlet delivery area.
     * For Brands with Outlet configuration only.
     *
     * Steps:
     * 1. Same steps as 3.1.1
     * 2. For body change the recipient's longitude and latitude out of delivery area
     * 3. Response expected: Error indicating address is outside deliverable range (422 or 403)
     *
     * @return void
     */
    public function testEstimateDeliveryFeeOutsideArea()
    {
        echo "\n\n✅ TEST CASE 3.1.2: Get delivery fee estimate with address outside delivery area (Unhappy Path)\n";
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
        echo "• Sender information added with location at coordinates: 1.3018914131301271, 103.83548392113393\n";

        // Display the request payload
        $requestPayload = $request->toArray();
        echo "\n• Request Payload (JSON):\n";
        echo json_encode($requestPayload, JSON_PRETTY_PRINT) . "\n";

        // Define and display the full URL based on environment
        $environment = $this->config->getEnvironment();
        $country     = $this->config->getCountry();

        // URLs as specified in the documentation
        $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/orders/fee";
        $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/orders/fee";
        $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/orders/fee";

        // Determine the actual URL to use
        $fullUrl = $sandboxUrl;
        if ('production' === $environment) {
            $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
        }

        echo "\nSTEP 2: Call the fee estimation endpoint with out-of-range address\n";
        echo "---------------------------------------------------------------\n";
        echo "• Full URL: " . $fullUrl . "\n";
        echo "• HTTP Method: POST\n";
        echo "• Environment: " . $environment . "\n";
        echo "• Country: " . $country . "\n";
        echo "• Sending request with out-of-range coordinates\n";

        try {
            // Request fee estimation
            $start  = microtime(true);
            $result = $this->client->orders()->estimateFee($request);
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

            // Verify the status code is either 422 Unprocessable Entity or 403 Forbidden
            // Both could be valid responses for out-of-range addresses depending on API implementation
            $this->assertContains(
                $e->getCode(),
                [422, 403],
                'Expected HTTP 422 Unprocessable Entity or 403 Forbidden status code'
            );
            echo "✓ Response status: " . $e->getCode() . " - Acceptable error code for out-of-range address\n";

            // Print details about the error for debugging
            echo "• Complete error message: " . $e->getMessage() . "\n";

            if ($e->getData()) {
                echo "• Error data: " . json_encode($e->getData(), JSON_PRETTY_PRINT) . "\n";
            }

            echo "\nSUMMARY: Successfully validated error for out-of-range address\n";
            echo "=========================================================\n";
            echo "• API Endpoint: " . $fullUrl . "\n";
            echo "• Correctly received error status code: " . $e->getCode() . "\n";
            echo "• API properly rejected the request as expected\n";
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

    /**
     * Test Case 3.1.3: Get delivery fee estimate without sender attribute (Unhappy Path)
     *
     * For Brands without Outlet configuration, excluding the sender attribute will
     * still generate an estimate, but the system will identify the sender location as
     * coming from the location vendor provided initially to create the Client Id.
     * This results in inaccurate estimation for the order.
     *
     * Steps:
     * 1. Same steps as 3.1.1 but remove sender attribute
     * 2. Expect 200 OK response, but estimates will be inaccurate
     *
     * @return void
     */
    public function testEstimateDeliveryFeeWithoutSender()
    {
        echo "\n\n✅ TEST CASE 3.1.3: Get delivery fee estimate without sender attribute (Unhappy Path)\n";
        echo "=====================================================================================\n\n";
        echo "STEP 1: Prepare order request WITHOUT sender information\n";
        echo "---------------------------------------------------------\n";

        // Create recipient with location in Singapore
        $recipient = TestAddresses::getCustomerContact();
        echo "• Recipient created with location at coordinates: 1.303166607308108, 103.83618242858377\n";

        // Create order request with a client order ID for tracing
        $clientOrderId = 'test-fee-no-sender-' . uniqid();
        $request       = new CreateOrderRequest(
            $recipient,
            23.50,             // Amount
            'Refreshing drink' // Description
        );
        $request->setClientOrderId($clientOrderId);
        echo "• Order request created with amount: 23.50\n";
        echo "• Client order ID: " . $clientOrderId . "\n";
        echo "• NOTE: Deliberately NOT setting sender information for this test\n";

        // Display the request payload
        $requestPayload = $request->toArray();
        echo "\n• Request Payload (JSON):\n";
        echo json_encode($requestPayload, JSON_PRETTY_PRINT) . "\n";

        // Define and display the full URL based on environment
        $environment = $this->config->getEnvironment();
        $country     = $this->config->getCountry();

        // URLs as specified in the documentation
        $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/orders/fee";
        $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/orders/fee";
        $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/orders/fee";

        // Determine the actual URL to use
        $fullUrl = $sandboxUrl;
        if ('production' === $environment) {
            $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
        }

        echo "\nSTEP 2: Call the fee estimation endpoint without sender attribute\n";
        echo "----------------------------------------------------------------\n";
        echo "• Full URL: " . $fullUrl . "\n";
        echo "• HTTP Method: POST\n";
        echo "• Environment: " . $environment . "\n";
        echo "• Country: " . $country . "\n";

        // For reference, also show the URL constructed from the Config object
        $configUrl = $this->config->getApiBaseUrl() . '/orders/fee';
        echo "• URL from Config: " . $configUrl . "\n";

        try {
            // Request fee estimation
            $start  = microtime(true);
            $result = $this->client->orders()->estimateFee($request);
            $end    = microtime(true);

            echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";
            echo "✓ Response status: 200 OK\n";

            echo "\nSTEP 3: Examine the response\n";
            echo "--------------------------\n";
            echo "• Response JSON:\n";
            echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

            echo "\nSTEP 4: Verify the response contains delivery fee estimate (potentially inaccurate)\n";
            echo "----------------------------------------------------------------------------\n";

            // Verify the response structure
            $this->assertIsArray($result);
            echo "✓ Response is a valid array\n";

            // Response should contain estimated_delivery_fee
            $this->assertArrayHasKey('estimated_delivery_fee', $result);
            echo "✓ Response contains 'estimated_delivery_fee' field\n";

            // Fee should be a positive number
            $this->assertIsNumeric($result['estimated_delivery_fee']);
            $this->assertGreaterThan(0, $result['estimated_delivery_fee']);
            echo "✓ Estimated delivery fee is a positive number: " . $result['estimated_delivery_fee'] . "\n";

            // Check if client order ID is included in response
            if (isset($result['client_order_id'])) {
                echo "✓ Response includes client_order_id: " . $result['client_order_id'] . "\n";
                // Verify it matches what we sent
                $this->assertEquals($clientOrderId, $result['client_order_id']);
                echo "✓ Returned client_order_id matches our request\n";
            }

            echo "\nSUMMARY: Successfully received delivery fee estimate (potentially inaccurate)\n";
            echo "====================================================================\n";
            echo "• API Endpoint: " . $fullUrl . "\n";
            echo "• Sender location: NOT PROVIDED (using vendor default location)\n";
            echo "• Recipient location: " . $recipient->getLocation()->getAddress() . "\n";
            echo "• Estimated delivery fee: " . $result['estimated_delivery_fee'] . "\n";
            echo "• NOTE: This estimate is potentially inaccurate because it uses the vendor's default\n";
            echo "  location instead of a specific sender location. For brands without outlet\n";
            echo "  configuration, always include the sender attribute for accurate estimates.\n";

        } catch (RequestException $e) {
            // For brands WITH outlet configuration, this might throw an error
            if ($e->getCode() === 422 && (
                strpos($e->getMessage(), 'sender location is required') !== false ||
                strpos($e->getMessage(), 'Sender location is required') !== false
            )) {
                echo "⚠️ This API appears to require sender information (configured with outlets):\n";
                echo "• API Endpoint: " . $fullUrl . "\n";
                echo "• Error message: " . $e->getMessage() . "\n";
                echo "• This indicates the account is configured with outlets and requires sender location\n";
                echo "• For brands WITH outlet configuration, sender attribute is mandatory\n";
                $this->markTestSkipped('API requires sender location (outlet configuration detected)');
            } else {
                echo "❌ Test failed with unexpected error:\n";
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
}
