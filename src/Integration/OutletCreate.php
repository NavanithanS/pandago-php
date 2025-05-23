<?php
namespace Nava\Pandago\Integration;

use Illuminate\Http\Request;
use Nava\Pandago\Client;
use Nava\Pandago\Config;
use Nava\Pandago\Exceptions\RequestException;
use Nava\Pandago\Models\Order\CancelOrderRequest;
use Nava\Pandago\Models\Order\CreateOrderRequest;
use Nava\Pandago\Models\Order\Order;
use Nava\Pandago\PandagoAddress;
use Nava\Pandago\PandagoClient;
use Log;

class OutletCreate
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
    protected $outletId;

    public function __construct()
    {
        $clientId = env('PANDAGO_CLIENT_ID');
        $keyId = env('PANDAGO_KEY_ID');
        $scope = env('PANDAGO_SCOPE');
        $privateKey =  file_get_contents(env('PANDAGO_PRIVATE_KEY'));
        $country = env('PANDAGO_COUNTRY');
        $environment = env('PANDAGO_ENVIRONMENT');
        $timeout = env('PANDAGO_TIMEOUT');

        $this->config = new Config($clientId, $keyId, $scope, $privateKey, $country, $environment, $timeout);
        $this->client = new PandagoClient($this->config);       
    }


    private function generateUniqueClientVendorId()
    {
        return 'outlet-test-' . uniqid();
    }

    public function testCreateNewOutlet()
    {
        try {
            // Get token and print authentication results
            // We're using reflection to access private methods/properties
            $reflectionClient = new \ReflectionClass($this->client);

            // First, get the token manager from the client
            $tokenManagerProp = $reflectionClient->getProperty('tokenManager');
            $tokenManagerProp->setAccessible(true);
            $tokenManager = $tokenManagerProp->getValue($this->client);

            // Now get the token from the token manager
            $reflectionTokenManager = new \ReflectionClass($tokenManager);

            // Get and display token (safely)
            $token     = $tokenManager->getToken();


            // Only display a small portion of the token for security
            $accessToken = $token->getAccessToken();
            $maskedToken = substr($accessToken, 0, 10) . '...' . substr($accessToken, -5);

           
        } catch (\Exception $e) {
            Log::info($e->getMessage());
        }

        // Generate a unique client vendor ID
        $clientVendorId = $this->generateUniqueClientVendorId(); 

        // Create outlet request with all required fields
        $request = PandagoAddress::createOutletRequest();

        // Display the request payload
        $requestPayload = $request->toArray();

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

        try {
            // Create the outlet
            $outlet = $this->client->outlets()->createOrUpdate($clientVendorId, $request);

            // Store the ID for potential future use
            $this->outletId = $clientVendorId;

            // Display additional outlet details
            $outletArray = $outlet->toArray();

            return $outletArray;

        } catch (RequestException $e) {
           
            if ($e->getData()) {
                 Log::info("Response data: " . json_encode($e->getData(), JSON_PRETTY_PRINT));
            }
            throw $e;
        }
    }


    public function testUpdateExistingOutlet()
    {
        // Create an outlet to update (or use an existing one from dependency)
        $clientVendorId = $this->outletId ?? $this->generateUniqueClientVendorId();

        // If we don't have an outlet from a previous test, create one
        if (! $this->testOutletId) {
            $initialRequest = TestAddresses::createOutletRequest();

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
            'Updated Outlet Name',                  // keep hardcoded (intentional)
            '10 Bayfront Avenue, Singapore 018956', // keep hardcoded (intentional)
            1.2839,                                 // keep hardcoded (intentional)
            103.8607,                               // keep hardcoded (intentional)
            TestAddresses::OUTLET_CITY,
            '+6599999999', // keep hardcoded (intentional)
            TestAddresses::OUTLET_CURRENCY,
            TestAddresses::OUTLET_LOCALE,
            'Updated outlet description' // keep hardcoded (intentional)
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

}
