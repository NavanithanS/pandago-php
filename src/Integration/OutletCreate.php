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
use Nava\Pandago\Models\Outlet\Outlet;

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
        return 'outlet-' . uniqid();
    }

    public function createOutlet($store)
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
        $request = PandagoAddress::createOutletRequest($store);

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

            return $clientVendorId;

        } catch (RequestException $e) {
           
            if ($e->getData()) {
                 Log::info("Response data: " . json_encode($e->getData(), JSON_PRETTY_PRINT));
            }
            throw $e;
        }
    }


    public function updateExistingOutlet($store)
    {
        // Create an outlet to update (or use an existing one from dependency)
        $clientVendorId = $store['pandago_store_id'];

        // Create update request with modified fields
        //Data will be pass from other side
        $updateRequest = PandagoAddress::createOutletRequest($store);

        // Display the request payload
        $requestPayload = $updateRequest->toArray();

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
            // Update the outlet
            $outlet = $this->client->outlets()->createOrUpdate($clientVendorId, $updateRequest);
            
            $outletArray = $outlet->toArray();
 
        } catch (RequestException $e) {
           
            if ($e->getData()) {
                 \Log::info("Response data: " . json_encode($e->getData(), JSON_PRETTY_PRINT));
            }
            throw $e;
        }
    }

}
