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
        $privateKey =  file_get_contents(env('PANDAGO_PRIVATE_KEY'));
        $country = env('PANDAGO_COUNTRY');
        $environment = env('PANDAGO_ENVIRONMENT');
        $timeout = env('PANDAGO_TIMEOUT');

        $this->config = new Config($clientId, $keyId, $scope, $privateKey, $country, $environment, $timeout);
        $this->client = new PandagoClient($this->config);       
    }

    public function getEstimateFee(Request $request)
    {

        $data = PandagoAddress::prepareData($request->all());

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

            // Calculate distance between sender and recipient using TestAddresses
            // $distance = PandagoAddress::getApproximateDistance();

            return $result;
            
        }catch (RequestException $e){
            Log::info('Fee estimation failed: ' . $e->getMessage());
        }
    }
    
}
