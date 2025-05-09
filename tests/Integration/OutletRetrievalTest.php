<?php
namespace Nava\Pandago\Tests\Integration;

use Nava\Pandago\Client;
use Nava\Pandago\Config;
use Nava\Pandago\Exceptions\RequestException;
use Nava\Pandago\Models\Outlet\CreateOutletRequest;
use Nava\Pandago\Tests\TestCase;

/**
 * Test Cases for Outlet Retrieval
 *
 * 10.1.1: Get Outlet (Happy Path)
 * 10.1.2: Get Outlet with Wrong client_vendor_id (Unhappy Path)
 */
class OutletRetrievalTest extends TestCase
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
    protected $testOutletId;

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
        // For outlets, there's typically no cleanup required as they can't be deleted via API
        parent::tearDown();
    }

    /**
     * Generate a unique client vendor ID for testing.
     *
     * @return string
     */
    private function generateUniqueClientVendorId()
    {
        return 'outlet-test-' . uniqid();
    }

    /**
     * Test Case 10.1.1: Get Outlet (Happy Path)
     *
     * Using the client_vendor_id value from Create Outlet API response or ID of the outlet to get.
     * Get all the outlet related information as response.
     *
     * Steps:
     * 1. [GET] Request to outlets/{client_vendor_id} endpoint
     * 2. For authorization use token generated from 1.2.1
     * 3. Verify successful response with status 200
     *
     * @return void
     */
    public function testGetOutlet()
    {
        echo "\n\n✅ TEST CASE 10.1.1: Get Outlet (Happy Path)\n";
        echo "========================================\n\n";
        echo "STEP 1: Create a test outlet to retrieve\n";
        echo "--------------------------------------\n";

        // Generate a unique client vendor ID
        $clientVendorId = $this->generateUniqueClientVendorId();
        echo "• Generated client vendor ID: " . $clientVendorId . "\n";

        // Create outlet request with all required fields
        $request = new CreateOutletRequest(
            'Test Outlet To Retrieve',           // name
            '1 Raffles Place, Singapore 048616', // address
            1.2842,                              // latitude
            103.8511,                            // longitude
            'Singapore',                         // city
            '+6588888888',                       // phone_number
            'SGD',                               // currency
            'en-SG',                             // locale
            'Test outlet for retrieval test'     // description
        );

        try {
            // Create the outlet
            $createdOutlet      = $this->client->outlets()->createOrUpdate($clientVendorId, $request);
            $this->testOutletId = $clientVendorId;

            echo "✓ Test outlet created successfully\n";
            echo "• Client Vendor ID: " . $createdOutlet->getClientVendorId() . "\n";
            echo "• Name: " . $createdOutlet->getName() . "\n";
            echo "• Address: " . $createdOutlet->getAddress() . "\n";

            echo "\nSTEP 2: Define API URL for retrieving outlet\n";
            echo "-------------------------------------------\n";

            // Define and display the full URL based on environment
            $environment = $this->config->getEnvironment();
            $country     = $this->config->getCountry();

            // URLs as specified in the documentation
            $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/outlets/{$clientVendorId}";
            $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/outlets/{$clientVendorId}";
            $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/outlets/{$clientVendorId}";

            // Determine the actual URL to use
            $fullUrl = $sandboxUrl;
            if ('production' === $environment) {
                $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
            }

            echo "• Full URL: " . $fullUrl . "\n";
            echo "• HTTP Method: GET\n";
            echo "• Environment: " . $environment . "\n";
            echo "• Country: " . $country . "\n";

            echo "\nSTEP 3: Retrieve the outlet\n";
            echo "-------------------------\n";

            // Get the outlet
            $start  = microtime(true);
            $outlet = $this->client->outlets()->get($clientVendorId);
            $end    = microtime(true);

            echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";
            echo "✓ Response status: 200 OK\n";

            echo "\nSTEP 4: Examine the response\n";
            echo "--------------------------\n";
            echo "• Response contains Outlet object with the following details:\n";
            echo "  - Client Vendor ID: " . $outlet->getClientVendorId() . "\n";
            echo "  - Name: " . $outlet->getName() . "\n";
            echo "  - Address: " . $outlet->getAddress() . "\n";
            echo "  - City: " . $outlet->getCity() . "\n";
            echo "  - Phone Number: " . $outlet->getPhoneNumber() . "\n";
            echo "  - Currency: " . $outlet->getCurrency() . "\n";
            echo "  - Locale: " . $outlet->getLocale() . "\n";

            // Display additional outlet details
            echo "\n• Complete Outlet Details:\n";
            $outletArray = $outlet->toArray();
            echo json_encode($outletArray, JSON_PRETTY_PRINT) . "\n";

            echo "\nSTEP 5: Verify the retrieved outlet matches the created outlet\n";
            echo "-----------------------------------------------------------\n";

            // Verify basic outlet details
            $this->assertEquals($clientVendorId, $outlet->getClientVendorId(), 'Client Vendor ID should match');
            echo "✓ Client Vendor ID matches\n";

            $this->assertEquals($createdOutlet->getName(), $outlet->getName(), 'Outlet name should match');
            echo "✓ Outlet name matches\n";

            $this->assertEquals($createdOutlet->getAddress(), $outlet->getAddress(), 'Address should match');
            echo "✓ Address matches\n";

            $this->assertEquals($createdOutlet->getLatitude(), $outlet->getLatitude(), 'Latitude should match');
            $this->assertEquals($createdOutlet->getLongitude(), $outlet->getLongitude(), 'Longitude should match');
            echo "✓ Coordinates match\n";

            $this->assertEquals($createdOutlet->getCity(), $outlet->getCity(), 'City should match');
            echo "✓ City matches\n";

            $this->assertEquals($createdOutlet->getPhoneNumber(), $outlet->getPhoneNumber(), 'Phone number should match');
            echo "✓ Phone number matches\n";

            $this->assertEquals($createdOutlet->getCurrency(), $outlet->getCurrency(), 'Currency should match');
            echo "✓ Currency matches\n";

            $this->assertEquals($createdOutlet->getLocale(), $outlet->getLocale(), 'Locale should match');
            echo "✓ Locale matches\n";

            echo "\nSUMMARY: Successfully retrieved outlet information\n";
            echo "==============================================\n";
            echo "• API Endpoint: " . $fullUrl . "\n";
            echo "• Client Vendor ID: " . $outlet->getClientVendorId() . "\n";
            echo "• Outlet Name: " . $outlet->getName() . "\n";
            echo "• All outlet details were retrieved correctly\n";

        } catch (RequestException $e) {
            echo "❌ Test failed with error:\n";
            echo "• Status code: " . $e->getCode() . "\n";
            echo "• Error message: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * Test Case 10.1.2: Get Outlet with Wrong client_vendor_id (Unhappy Path)
     *
     * Using a wrong client_vendor_id should result in a 404 error.
     *
     * Steps:
     * 1. Same steps as 10.1.1 but with a wrong client_vendor_id
     * 2. Verify error response with status 404 and message "Outlet not found"
     *
     * @return void
     */
    public function testGetOutletWithWrongId()
    {
        echo "\n\n✅ TEST CASE 10.1.2: Get Outlet with Wrong client_vendor_id (Unhappy Path)\n";
        echo "===================================================================\n\n";
        echo "STEP 1: Define an invalid/non-existent client vendor ID\n";
        echo "---------------------------------------------------\n";

        // Generate a random invalid client vendor ID
        $invalidClientVendorId = 'non-existent-outlet-' . uniqid();
        echo "• Invalid client vendor ID: " . $invalidClientVendorId . "\n";

        echo "\nSTEP 2: Define API URL for retrieving outlet\n";
        echo "-------------------------------------------\n";

        // Define and display the full URL based on environment
        $environment = $this->config->getEnvironment();
        $country     = $this->config->getCountry();

        // URLs as specified in the documentation
        $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/outlets/{$invalidClientVendorId}";
        $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/outlets/{$invalidClientVendorId}";
        $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/outlets/{$invalidClientVendorId}";

        // Determine the actual URL to use
        $fullUrl = $sandboxUrl;
        if ('production' === $environment) {
            $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
        }

        echo "• Full URL: " . $fullUrl . "\n";
        echo "• HTTP Method: GET\n";
        echo "• Environment: " . $environment . "\n";
        echo "• Country: " . $country . "\n";

        echo "\nSTEP 3: Attempt to retrieve the non-existent outlet\n";
        echo "------------------------------------------------\n";

        try {
            // Attempt to get the outlet with invalid ID
            $start  = microtime(true);
            $outlet = $this->client->outlets()->get($invalidClientVendorId);
            $end    = microtime(true);

            // If we get here, the test has failed because we expected an exception
            echo "⚠️ Test failed: Expected 404 error but got success response\n";
            $this->fail('Expected 404 Not Found but received success response');

        } catch (RequestException $e) {
            $end = microtime(true);
            echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";

            // Verify we got a 404 Not Found error
            $this->assertEquals(404, $e->getCode(), 'Expected HTTP 404 Not Found');
            echo "✓ Received expected 404 Not Found error\n";

            // Verify the error message
            $expectedErrorMsg = "Outlet not found";
            $this->assertStringContainsString($expectedErrorMsg, $e->getMessage(), 'Error should indicate outlet not found');
            echo "✓ Error message correctly indicates outlet not found: " . $e->getMessage() . "\n";

            // If the error response data is available, verify it matches the expected format
            if ($e->getData()) {
                $errorData = $e->getData();
                $this->assertArrayHasKey('message', $errorData, 'Error response should contain message field');
                $this->assertEquals($expectedErrorMsg, $errorData['message'], 'Error message should match expected value');
                echo "✓ Error response data contains expected message: " . json_encode($errorData) . "\n";
            }
        }

        echo "\nSUMMARY: Successfully validated error handling for non-existent outlet\n";
        echo "================================================================\n";
        echo "• API Endpoint: " . $fullUrl . "\n";
        echo "• Invalid Client Vendor ID: " . $invalidClientVendorId . "\n";
        echo "• Correctly received 404 Not Found error\n";
        echo "• Error message correctly indicates 'Outlet not found'\n";
    }

    /**
     * Test retrieving an existing outlet by ID (using a known ID).
     *
     * This test is for cases where you have a known outlet ID and want to verify
     * retrieval works without having to create a new outlet first.
     *
     * @return void
     */
    public function testGetExistingOutlet()
    {
        // Check if we have a configured test outlet ID in config
        $config           = $this->getConfig();
        $existingOutletId = $config['test_outlet_id'] ?? null;

        if (! $existingOutletId) {
            $this->markTestSkipped('Skipping test - no test_outlet_id configured in tests/config.php');
            return;
        }

        echo "\n\n✅ TEST: Get Existing Outlet (Using Configured ID)\n";
        echo "==============================================\n\n";
        echo "STEP 1: Define API URL for retrieving existing outlet\n";
        echo "------------------------------------------------\n";

        echo "• Using configured client vendor ID: " . $existingOutletId . "\n";

        // Define and display the full URL based on environment
        $environment = $this->config->getEnvironment();
        $country     = $this->config->getCountry();

        // URLs as specified in the documentation
        $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/outlets/{$existingOutletId}";
        $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/outlets/{$existingOutletId}";
        $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/outlets/{$existingOutletId}";

        // Determine the actual URL to use
        $fullUrl = $sandboxUrl;
        if ('production' === $environment) {
            $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
        }

        echo "• Full URL: " . $fullUrl . "\n";
        echo "• HTTP Method: GET\n";
        echo "• Environment: " . $environment . "\n";
        echo "• Country: " . $country . "\n";

        echo "\nSTEP 2: Retrieve the existing outlet\n";
        echo "----------------------------------\n";

        try {
            // Get the outlet
            $start  = microtime(true);
            $outlet = $this->client->outlets()->get($existingOutletId);
            $end    = microtime(true);

            echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";
            echo "✓ Response status: 200 OK\n";

            echo "\nSTEP 3: Examine the response\n";
            echo "--------------------------\n";
            echo "• Response contains Outlet object with the following details:\n";
            echo "  - Client Vendor ID: " . $outlet->getClientVendorId() . "\n";
            echo "  - Name: " . $outlet->getName() . "\n";
            echo "  - Address: " . $outlet->getAddress() . "\n";
            echo "  - City: " . $outlet->getCity() . "\n";
            echo "  - Phone Number: " . $outlet->getPhoneNumber() . "\n";

            // Display the full outlet details
            echo "\n• Complete Outlet Details:\n";
            $outletArray = $outlet->toArray();
            echo json_encode($outletArray, JSON_PRETTY_PRINT) . "\n";

            echo "\nSUMMARY: Successfully retrieved existing outlet information\n";
            echo "=======================================================\n";
            echo "• API Endpoint: " . $fullUrl . "\n";
            echo "• Client Vendor ID: " . $outlet->getClientVendorId() . "\n";
            echo "• Outlet Name: " . $outlet->getName() . "\n";

        } catch (RequestException $e) {
            echo "❌ Test failed with error:\n";
            echo "• Status code: " . $e->getCode() . "\n";
            echo "• Error message: " . $e->getMessage() . "\n";

            if ($e->getCode() === 404) {
                echo "⚠️ The configured test_outlet_id may not exist in this environment\n";
                $this->markTestSkipped('The configured test_outlet_id does not exist');
            } else {
                throw $e;
            }
        }
    }
}
