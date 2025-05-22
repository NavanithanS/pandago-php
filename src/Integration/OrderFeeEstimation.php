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

class OrderFeeEstimation 
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
        $privateKey = env('PANDAGO_PRIVATE_KEY');
        $country = env('PANDAGO_COUNTRY');
        $environment = env('PANDAGO_ENVIRONMENT');
        $timeout = env('PANDAGO_TIMEOUT');

        $this->config = new Config($clientId, $keyId, $scope, $privateKey, $country, $environment, $timeout);
        $this->client = new PandagoClient($this->config);       
    }

    public function getEstimateFee(Request $request)
    {
        // \Log::info('Request: '. print_r($request->all(), true));

        $data = PandagoAddress::prepareData($request->all());

        // \Log::info('Data: '. print_r($data, true));

        //recipient details
        $recipient = PandagoAddress::getCustomerContact($data);

        $clientOrderId = PandagoAddress::generateClientOrderId('fee');

        $orderRequest = new CreateOrderRequest(
            $recipient,
            $data['subtotal'],
            'Creating Order',
        );

        $orderRequest->setClientOrderId($clientOrderId);

        //set sender 
        // $store = Store::find($data['store_id']);
        $sender = PandagoAddress::getOutletContact();
        $orderRequest->setSender($sender);

        //can print the payload 
        $orderRequestPayload = $orderRequest->toArray();

        //TO DO: need to change if push to production
        $environment = env('PANDAGO_ENVIRONMENT');
        $country = env('PANDAGO_COUNTRY');

        $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/orders/fee";
        $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/orders/fee";
        $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/orders/fee";

        $fullUrl = $sandboxUrl;
        if ('production' === $environment) {
            $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
        }

        // For reference, also show the URL constructed from the Config object
        $configUrl = $this->config->getApiBaseUrl() . '/orders/fee';

        try{
            $result = $this->client->orders()->estimateFee($orderRequest);
            
            // $this->assertIsArray($result);
            // $this->assertArrayHasKey('estimated_delivery_fee', $result);

            // $this->assertIsNumeric($result['estimated_delivery_fee']);
            // $this->assertGreaterThan(0, $result['estimated_delivery_fee']);
            
            // // Check if client order ID is included in response
            // if (isset($result['client_order_id'])) {
            //     // Verify it matches what we sent
            //     $this->assertEquals($clientOrderId, $result['client_order_id']);
            // }

            // Calculate distance between sender and recipient using TestAddresses
            // $distance = PandagoAddress::getApproximateDistance();

            return $result;
            
        }catch (RequestException $e){
            $this->markTestSkipped('Fee estimation failed: ' . $e->getMessage());
        }
    }
    
}
