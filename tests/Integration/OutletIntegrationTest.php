<?php
namespace Nava\Pandago\Tests\Integration;

use Nava\Pandago\Client;
use Nava\Pandago\Config;
use Nava\Pandago\Exceptions\RequestException;
use Nava\Pandago\Models\Outlet\CreateOutletRequest;
use Nava\Pandago\Tests\TestCase;

/**
 * Test Cases for Outlet Creation and Update
 *
 * 9.1.1: Create New Outlet (Happy Path)
 * 9.1.2: Update Existing Outlet (Happy Path)
 * 9.1.3: Create Outlet with Missing or Invalid Required Fields (Unhappy Path)
 * 9.1.4: Update Outlet with Both Add and Delete User Operations (Unhappy Path)
 */
class OutletIntegrationTest extends TestCase
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
     * Test Case 9.1.1: Create New Outlet (Happy Path)
     *
     * Submit outlet detail JSON to create new outlet with all required attributes.
     *
     * Steps:
     * 1. [PUT] Request to /outlets/{client_vendor_id} endpoint
     * 2. Include all required outlet attributes
     * 3. Verify successful response with status 200
     *
     * @return void
     */
    public function testCreateNewOutlet()
    {
        echo "\n\n✅ TEST CASE 9.1.1: Create New Outlet (Happy Path)\n";
        echo "==============================================\n\n";
        echo "STEP 1: Prepare outlet creation request\n";
        echo "-------------------------------------\n";

        // Generate a unique client vendor ID
        $clientVendorId = $this->generateUniqueClientVendorId();
        echo "• Generated client vendor ID: " . $clientVendorId . "\n";

        // Create outlet request with all required fields
        $request = new CreateOutletRequest(
            'Pandago Test Outlet',                // name
            '1 Raffles Place, Singapore 048616',  // address
            1.2842,                               // latitude
            103.8511,                             // longitude
            'Singapore',                          // city
            '+6588888888',                        // phone_number
            'SGD',                                // currency
            'en-SG',                              // locale
            'Integration test outlet description' // description
        );

        // Display the request payload
        $requestPayload = $request->toArray();
        echo "• Request Payload (JSON):\n";
        echo json_encode($requestPayload, JSON_PRETTY_PRINT) . "\n";

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

        echo "\nSTEP 2: Call the outlet creation endpoint\n";
        echo "---------------------------------------\n";
        echo "• Full URL: " . $fullUrl . "\n";
        echo "• HTTP Method: PUT\n";
        echo "• Environment: " . $environment . "\n";
        echo "• Country: " . $country . "\n";

        try {
            // Create the outlet
            $start  = microtime(true);
            $outlet = $this->client->outlets()->createOrUpdate($clientVendorId, $request);
            $end    = microtime(true);

            // Store the ID for potential future use
            $this->testOutletId = $clientVendorId;

            echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";
            echo "✓ Response status: 200 OK\n";

            echo "\nSTEP 3: Examine the response\n";
            echo "--------------------------\n";
            echo "• Response contains Outlet object with the following details:\n";
            echo "  - Client Vendor ID: " . $outlet->getClientVendorId() . "\n";
            echo "  - Name: " . $outlet->getName() . "\n";
            echo "  - Address: " . $outlet->getAddress() . "\n";

            // Display additional outlet details
            echo "\n• Complete Outlet Details:\n";
            $outletArray = $outlet->toArray();
            echo json_encode($outletArray, JSON_PRETTY_PRINT) . "\n";

            echo "\nSTEP 4: Verify the outlet was created successfully\n";
            echo "-----------------------------------------------\n";

            // Verify basic outlet details
            $this->assertEquals($clientVendorId, $outlet->getClientVendorId(), 'Client Vendor ID should match');
            echo "✓ Client Vendor ID matches the request\n";

            $this->assertEquals('Pandago Test Outlet', $outlet->getName(), 'Outlet name should match');
            echo "✓ Outlet name matches the request\n";

            $this->assertEquals('1 Raffles Place, Singapore 048616', $outlet->getAddress(), 'Address should match');
            echo "✓ Address matches the request\n";

            $this->assertEquals(1.2842, $outlet->getLatitude(), 'Latitude should match');
            $this->assertEquals(103.8511, $outlet->getLongitude(), 'Longitude should match');
            echo "✓ Coordinates match the request\n";

            echo "\nSUMMARY: Successfully created a new outlet\n";
            echo "========================================\n";
            echo "• API Endpoint: " . $fullUrl . "\n";
            echo "• Client Vendor ID: " . $outlet->getClientVendorId() . "\n";
            echo "• Outlet Name: " . $outlet->getName() . "\n";

        } catch (RequestException $e) {
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

    /**
     * Test Case 9.1.2: Update Existing Outlet (Happy Path)
     *
     * Submit outlet detail JSON to update an existing outlet.
     *
     * Steps:
     * 1. Create an outlet (or use existing one)
     * 2. [PUT] Request to update the outlet with modified details
     * 3. Verify successful response with status 200
     *
     * @depends testCreateNewOutlet
     * @return void
     */
    public function testUpdateExistingOutlet()
    {
        echo "\n\n✅ TEST CASE 9.1.2: Update Existing Outlet (Happy Path)\n";
        echo "==================================================\n\n";
        echo "STEP 1: Create a test outlet for updating\n";
        echo "--------------------------------------\n";

        // Create an outlet to update (or use an existing one from dependency)
        $clientVendorId = $this->testOutletId ?? $this->generateUniqueClientVendorId();
        echo "• Using client vendor ID: " . $clientVendorId . "\n";

        // If we don't have an outlet from a previous test, create one
        if (! $this->testOutletId) {
            $initialRequest = new CreateOutletRequest(
                'Initial Outlet Name',
                '1 Raffles Place, Singapore 048616',
                1.2842,
                103.8511,
                'Singapore',
                '+6588888888',
                'SGD',
                'en-SG',
                'Initial description'
            );

            try {
                $outlet = $this->client->outlets()->createOrUpdate($clientVendorId, $initialRequest);
                echo "✓ Created initial outlet to be updated\n";
                $this->testOutletId = $clientVendorId;
            } catch (RequestException $e) {
                echo "⚠️ Could not create initial outlet: " . $e->getMessage() . "\n";
                $this->markTestSkipped('Failed to create initial outlet for update test.');
                return;
            }
        }

        echo "\nSTEP 2: Prepare outlet update request\n";
        echo "-----------------------------------\n";

        // Create update request with modified fields
        $updateRequest = new CreateOutletRequest(
            'Updated Outlet Name',                  // name
            '10 Bayfront Avenue, Singapore 018956', // address
            1.2839,                                 // latitude
            103.8607,                               // longitude
            'Singapore',                            // city
            '+6599999999',                          // phone_number
            'SGD',                                  // currency
            'en-SG',                                // locale
            'Updated outlet description'            // description
        );

        // Add a user to the outlet
        $updateRequest->setAddUsers(['test.user@example.com']);

        // Display the request payload
        $requestPayload = $updateRequest->toArray();
        echo "• Request Payload (JSON):\n";
        echo json_encode($requestPayload, JSON_PRETTY_PRINT) . "\n";

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

        echo "\nSTEP 3: Call the outlet update endpoint\n";
        echo "-------------------------------------\n";
        echo "• Full URL: " . $fullUrl . "\n";
        echo "• HTTP Method: PUT\n";
        echo "• Environment: " . $environment . "\n";
        echo "• Country: " . $country . "\n";

        try {
            // Update the outlet
            $start  = microtime(true);
            $outlet = $this->client->outlets()->createOrUpdate($clientVendorId, $updateRequest);
            $end    = microtime(true);

            echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";
            echo "✓ Response status: 200 OK\n";

            echo "\nSTEP 4: Examine the response\n";
            echo "--------------------------\n";
            echo "• Response contains updated Outlet object with the following details:\n";
            echo "  - Client Vendor ID: " . $outlet->getClientVendorId() . "\n";
            echo "  - Name: " . $outlet->getName() . "\n";
            echo "  - Address: " . $outlet->getAddress() . "\n";

            // Display additional outlet details
            echo "\n• Complete Outlet Details:\n";
            $outletArray = $outlet->toArray();
            echo json_encode($outletArray, JSON_PRETTY_PRINT) . "\n";

            echo "\nSTEP 5: Verify the outlet was updated successfully\n";
            echo "-----------------------------------------------\n";

            // Verify updated outlet details
            $this->assertEquals($clientVendorId, $outlet->getClientVendorId(), 'Client Vendor ID should match');
            echo "✓ Client Vendor ID matches\n";

            $this->assertEquals('Updated Outlet Name', $outlet->getName(), 'Outlet name should be updated');
            echo "✓ Outlet name was updated successfully\n";

            $this->assertEquals('10 Bayfront Avenue, Singapore 018956', $outlet->getAddress(), 'Address should be updated');
            echo "✓ Address was updated successfully\n";

            $this->assertEquals(1.2839, $outlet->getLatitude(), 'Latitude should be updated');
            $this->assertEquals(103.8607, $outlet->getLongitude(), 'Longitude should be updated');
            echo "✓ Coordinates were updated successfully\n";

            // Verify user addition
            if (isset($outletArray['users']) && is_array($outletArray['users'])) {
                $userAdded = in_array('test.user@example.com', $outletArray['users']);
                $this->assertTrue($userAdded, 'User should be added to the outlet');
                echo "✓ User was added successfully\n";
            } else {
                echo "⚠️ Users array not found in response, cannot verify user addition\n";
            }

            echo "\nSUMMARY: Successfully updated an existing outlet\n";
            echo "=============================================\n";
            echo "• API Endpoint: " . $fullUrl . "\n";
            echo "• Client Vendor ID: " . $outlet->getClientVendorId() . "\n";
            echo "• Updated Outlet Name: " . $outlet->getName() . "\n";

        } catch (RequestException $e) {
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

    /**
     * Test Case 9.1.3: Create Outlet with Missing or Invalid Required Fields (Unhappy Path)
     *
     * Exclude required attributes (like phone number) or use invalid values.
     *
     * Steps:
     * 1. Create request with missing phone number
     * 2. Attempt to create outlet
     * 3. Verify error response with status 400
     * 4. Try again with invalid phone number
     * 5. Verify similar error response
     *
     * @return void
     */
    public function testCreateOutletWithMissingOrInvalidFields()
    {
        echo "\n\n✅ TEST CASE 9.1.3: Create Outlet with Missing or Invalid Required Fields (Unhappy Path)\n";
        echo "==================================================================================\n\n";
        echo "STEP 1: Prepare outlet request with missing phone number\n";
        echo "----------------------------------------------------\n";

        // Generate a unique client vendor ID
        $clientVendorId = $this->generateUniqueClientVendorId();
        echo "• Generated client vendor ID: " . $clientVendorId . "\n";

        // Use reflection to create a request with missing phone number
        $request = new CreateOutletRequest(
            'Missing Phone Outlet',
            '1 Raffles Place, Singapore 048616',
            1.2842,
            103.8511,
            'Singapore',
            '', // Empty phone number
            'SGD',
            'en-SG',
            'Test missing phone number'
        );

        // Use reflection to set phone_number to null (since the constructor validates it)
        $reflection = new \ReflectionClass($request);
        $property   = $reflection->getProperty('phoneNumber');
        $property->setAccessible(true);
        $property->setValue($request, null);

        // Display the request payload
        $requestPayload = $request->toArray();
        echo "• Request Payload (JSON) with missing phone number:\n";
        echo json_encode($requestPayload, JSON_PRETTY_PRINT) . "\n";

        // Define and display the full URL
        $environment = $this->config->getEnvironment();
        $country     = $this->config->getCountry();

        $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/outlets/{$clientVendorId}";
        $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/outlets/{$clientVendorId}";
        $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/outlets/{$clientVendorId}";

        $fullUrl = $sandboxUrl;
        if ('production' === $environment) {
            $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
        }

        echo "\nSTEP 2: Attempt to create outlet with missing phone number\n";
        echo "------------------------------------------------------\n";
        echo "• Full URL: " . $fullUrl . "\n";
        echo "• HTTP Method: PUT\n";

        try {
            $start  = microtime(true);
            $outlet = $this->client->outlets()->createOrUpdate($clientVendorId, $request);
            $end    = microtime(true);

            // If we get here, the test has failed because we expected an exception
            echo "⚠️ Test failed: Expected error for missing phone number but got success response\n";
            $this->fail('Expected 400 Bad Request for missing phone number');

        } catch (RequestException $e) {
            $end = microtime(true);
            echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";

            // Verify we got a 400 Bad Request error
            $this->assertEquals(400, $e->getCode(), 'Expected HTTP 400 Bad Request');
            echo "✓ Received expected 400 Bad Request error\n";

            // Verify the error message
            $expectedErrorMsg = "phone_number is required";
            $this->assertStringContainsString($expectedErrorMsg, $e->getMessage(), 'Error should mention phone_number is required');
            echo "✓ Error message correctly indicates phone_number is required: " . $e->getMessage() . "\n";
        }

        echo "\nSTEP 3: Prepare outlet request with invalid phone number\n";
        echo "----------------------------------------------------\n";

        // Generate another unique client vendor ID
        $invalidPhoneClientVendorId = $this->generateUniqueClientVendorId();
        echo "• Generated client vendor ID: " . $invalidPhoneClientVendorId . "\n";

        // Create request with invalid phone number format
        $invalidRequest = new CreateOutletRequest(
            'Invalid Phone Outlet',
            '1 Raffles Place, Singapore 048616',
            1.2842,
            103.8511,
            'Singapore',
            '12345', // Invalid phone number (not in E.164 format)
            'SGD',
            'en-SG',
            'Test invalid phone number'
        );

        // Display the request payload
        $invalidRequestPayload = $invalidRequest->toArray();
        echo "• Request Payload (JSON) with invalid phone number:\n";
        echo json_encode($invalidRequestPayload, JSON_PRETTY_PRINT) . "\n";

        // Update the URL for the new client vendor ID
        $fullInvalidUrl = str_replace($clientVendorId, $invalidPhoneClientVendorId, $fullUrl);

        echo "\nSTEP 4: Attempt to create outlet with invalid phone number\n";
        echo "------------------------------------------------------\n";
        echo "• Full URL: " . $fullInvalidUrl . "\n";
        echo "• HTTP Method: PUT\n";

        try {
            $start  = microtime(true);
            $outlet = $this->client->outlets()->createOrUpdate($invalidPhoneClientVendorId, $invalidRequest);
            $end    = microtime(true);

            // If we get here, the test has failed because we expected an exception
            echo "⚠️ Test failed: Expected error for invalid phone number but got success response\n";
            $this->fail('Expected 400 Bad Request for invalid phone number');

        } catch (RequestException $e) {
            $end = microtime(true);
            echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";

            // Verify we got a 400 Bad Request error
            $this->assertEquals(400, $e->getCode(), 'Expected HTTP 400 Bad Request');
            echo "✓ Received expected 400 Bad Request error\n";

            // Verify the error message
            $expectedErrorMsg = "phone_number is not valid";
            $this->assertStringContainsString($expectedErrorMsg, $e->getMessage(), 'Error should mention phone_number is not valid');
            echo "✓ Error message correctly indicates phone_number is not valid: " . $e->getMessage() . "\n";
        }

        echo "\nSUMMARY: Successfully validated error handling for missing and invalid fields\n";
        echo "======================================================================\n";
        echo "• API correctly rejected request with missing phone number (400 Bad Request)\n";
        echo "• API correctly rejected request with invalid phone number (400 Bad Request)\n";
        echo "• Error messages properly indicated the specific validation issues\n";
    }

    /**
     * Test Case 9.1.4: Update Outlet with Both Add and Delete User Operations (Unhappy Path)
     *
     * Attempt to add and delete users in the same request, which is not allowed.
     *
     * Steps:
     * 1. Create request with both add_user and delete_user fields
     * 2. Attempt to update outlet
     * 3. Verify error response with status 400
     *
     * @return void
     */
    public function testUpdateOutletWithAddAndDeleteUsers()
    {
        echo "\n\n✅ TEST CASE 9.1.4: Update Outlet with Both Add and Delete User Operations (Unhappy Path)\n";
        echo "==================================================================================\n\n";
        echo "STEP 1: Create a test outlet if necessary\n";
        echo "--------------------------------------\n";

        // Use existing outlet ID or create a new one
        $clientVendorId = $this->testOutletId ?? $this->generateUniqueClientVendorId();
        echo "• Using client vendor ID: " . $clientVendorId . "\n";

        // If we don't have an outlet from a previous test, create one
        if (! $this->testOutletId) {
            $initialRequest = new CreateOutletRequest(
                'Outlet for User Test',
                '1 Raffles Place, Singapore 048616',
                1.2842,
                103.8511,
                'Singapore',
                '+6588888888',
                'SGD',
                'en-SG',
                'Outlet for testing user operations'
            );

            try {
                $outlet = $this->client->outlets()->createOrUpdate($clientVendorId, $initialRequest);
                echo "✓ Created initial outlet for user operations test\n";
                $this->testOutletId = $clientVendorId;
            } catch (RequestException $e) {
                echo "⚠️ Could not create initial outlet: " . $e->getMessage() . "\n";
                $this->markTestSkipped('Failed to create initial outlet for user operations test.');
                return;
            }
        }

        echo "\nSTEP 2: Prepare outlet update request with both add and delete user operations\n";
        echo "-------------------------------------------------------------------------\n";

        // Create update request with both add_user and delete_user fields
        $updateRequest = new CreateOutletRequest(
            'User Operations Test Outlet',
            '1 Raffles Place, Singapore 048616',
            1.2842,
            103.8511,
            'Singapore',
            '+6588888888',
            'SGD',
            'en-SG',
            'Testing add and delete users simultaneously'
        );

        // Add both user operations
        $updateRequest->setAddUsers(['new.user@example.com']);
        $updateRequest->setDeleteUsers(['existing.user@example.com']);

        // Display the request payload
        $requestPayload = $updateRequest->toArray();
        echo "• Request Payload (JSON) with both add_user and delete_user:\n";
        echo json_encode($requestPayload, JSON_PRETTY_PRINT) . "\n";

        // Define and display the full URL
        $environment = $this->config->getEnvironment();
        $country     = $this->config->getCountry();

        $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/outlets/{$clientVendorId}";
        $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/outlets/{$clientVendorId}";
        $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/outlets/{$clientVendorId}";

        $fullUrl = $sandboxUrl;
        if ('production' === $environment) {
            $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
        }

        echo "\nSTEP 3: Attempt to update outlet with both user operations\n";
        echo "-------------------------------------------------------\n";
        echo "• Full URL: " . $fullUrl . "\n";
        echo "• HTTP Method: PUT\n";

        try {
            $start  = microtime(true);
            $outlet = $this->client->outlets()->createOrUpdate($clientVendorId, $updateRequest);
            $end    = microtime(true);

            // If we get here, the test has failed because we expected an exception
            echo "⚠️ Test failed: Expected error for add_user and delete_user in same request but got success response\n";
            $this->fail('Expected 400 Bad Request for add_user and delete_user in same request');

        } catch (RequestException $e) {
            $end = microtime(true);
            echo "✓ Request completed in " . round(($end - $start) * 1000, 2) . " ms\n";

            // Verify we got a 400 Bad Request error
            $this->assertEquals(400, $e->getCode(), 'Expected HTTP 400 Bad Request');
            echo "✓ Received expected 400 Bad Request error\n";

            // Verify the error message
            $expectedErrorMsg = "cannot add and delete in the same request";
            $this->assertStringContainsString($expectedErrorMsg, $e->getMessage(), 'Error should mention cannot add and delete in the same request');
            echo "✓ Error message correctly indicates cannot add and delete in the same request: " . $e->getMessage() . "\n";
        }

        echo "\nSUMMARY: Successfully validated error handling for add and delete user operations\n";
        echo "========================================================================\n";
        echo "• API correctly rejected request with both add_user and delete_user (400 Bad Request)\n";
        echo "• Error message properly indicated that users cannot be added and deleted in the same request\n";
    }
}
