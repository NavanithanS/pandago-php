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

class OrderCreate 
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
    protected $orderId;

    public function __construct()
    {
        $clientId = env('PANDAGO_CLIENT_ID');
        $keyId = env('PANDAGO_KEY_ID');
        $scope = env('PANDAGO_SCOPE');
        $privateKey =  file_get_contents(storage_path(env('PANDAGO_PRIVATE_KEY')));
        $country = env('PANDAGO_COUNTRY');
        $environment = env('PANDAGO_ENVIRONMENT');
        $timeout = env('PANDAGO_TIMEOUT');

        $this->config = new Config($clientId, $keyId, $scope, $privateKey, $country, $environment, $timeout);
        $this->client = new PandagoClient($this->config);       
    }

    public function createOrder($request, $store)
    {
        
        $data = PandagoAddress::prepareCreateOrderData($request);
        
        // Create recipient
        $recipient = PandagoAddress::getCustomerContact($data);

        // Create order request with a client order ID for tracing
        $clientOrderId = $data['refno'];

        $orderRequest = new CreateOrderRequest(
            $recipient,
            $data['subtotal'],
            'Creating Order',
        );


        $orderRequest->setClientOrderId($clientOrderId);

        //set sender 
        $sender = PandagoAddress::getOutletContact($store);
        $orderRequest->setSender($sender);
        $orderRequest->setPaymentMethod('PAID');
        $orderRequest->setColdbagNeeded(true);

        //for print to check the data
        $requestOrderPayload = $orderRequest->toArray();

        // Log::info(print_r($requestOrderPayload, true));

        // Define and display the full URL based on environment
        $environment = $this->config->getEnvironment();
        $country     = $this->config->getCountry();

        // URLs as specified in the documentation
        $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/orders";
        $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/orders";
        $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/orders";

        // Determine the actual URL to use
        $fullUrl = $sandboxUrl;
        if ('production' === $environment) {
            $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
        }

        $configUrl = $this->config->getApiBaseUrl() . '/orders';

        try {
            $order = $this->client->orders()->create($orderRequest);

            // Store order ID for cleanup
            $this->orderId = $order->getOrderId();

            //Display additional order details
            $orderArray = $order->toArray();
            $orderArray['order_id'] = $this->orderId;

            // Verify recipient details using TestAddresses constants
            $orderRecipient = $order->getRecipient();

            // Verify recipient location
            $recipientLocation = $orderRecipient->getLocation();

            return $orderArray;

        } catch (RequestException $e) {
            // Handle common integration test failures
            if ($e->getCode() === 404 && strpos($e->getMessage(), 'Outlet not found') !== false) {
                Log::info('Integration test failed: Outlet not found. Please configure a valid client vendor ID.');
            } elseif ($e->getCode() === 422 && strpos($e->getMessage(), 'No Branch found') !== false) {
                Log::info('Integration test failed: No branch found that is close enough to the given sender coordinates.');
            } else {
                throw $e;
            }
        }

    }
}
