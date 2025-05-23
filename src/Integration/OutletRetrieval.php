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

    public function testGetOutlet()
    {
        // Generate a unique client vendor ID
        $clientVendorId = $this->generateUniqueClientVendorId();

        // Create outlet request with all required fields
        $request = PandagoAddress::createOutletRequest();

        try {
            // Create the outlet
            $createdOutlet      = $this->client->outlets()->createOrUpdate($clientVendorId, $request);
            $this->testOutletId = $clientVendorId;

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

            // Get the outlet
            $outlet = $this->client->outlets()->get($clientVendorId);

            // Display additional outlet details
            $outletArray = $outlet->toArray();

            return $outletArray;
          
        } catch (RequestException $e) {
            throw $e;
        }
    }

    public function testGetExistingOutlet()
    {
        // Check if we have a configured test outlet ID in config
        $config           = $this->getConfig();
        $existingOutletId = $config['test_outlet_id'] ?? null;

        if (! $existingOutletId) {
            Log::info('Skipping test - no test_outlet_id configured in tests/config.php');
            return;
        }

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

        try {
            // Get the outlet
            $outlet = $this->client->outlets()->get($existingOutletId);

            // Display the full outlet details
            $outletArray = $outlet->toArray();

        } catch (RequestException $e) {
            
            if ($e->getCode() === 404) {
                Log::info('The configured test_outlet_id does not exist');
            } else {
                throw $e;
            }
        }
    }
}
