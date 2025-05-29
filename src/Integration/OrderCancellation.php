<?php
namespace Nava\Pandago\Integration;

use Nava\Pandago\Client;
use Nava\Pandago\Config;
use Nava\Pandago\Exceptions\RequestException;
use Nava\Pandago\Models\Order\CancelOrderRequest;
use Nava\Pandago\Models\Order\CreateOrderRequest;
use Nava\Pandago\Models\Order\Order;
use Nava\Pandago\PandagoClient;
use Nava\Pandago\Tests\Helpers\TestAddresses;
use Nava\Pandago\Tests\TestCase;
use Nava\Pandago\Tests\Util\MockCallbackServer;

class OrderCancellation 
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


    public function cancelOrder($order)
    {   
        try {
            //get the order            
            $orderId = $order['order_id'];
            $reason = 'MISTAKE_ERROR';

            $getOrder   = $this->client->orders()->get($orderId);
            
            $cancelRequest = new CancelOrderRequest($reason);

            // Define and display the full URL based on environment
            $environment = $this->config->getEnvironment();
            $country     = $this->config->getCountry();

             // URLs as specified in the documentation
            $sandboxUrl        = "https://pandago-api-sandbox.deliveryhero.io/{$country}/api/v1/orders/{$getOrder->getOrderId()}";
            $productionUrlApac = "https://pandago-api-apse.deliveryhero.io/{$country}/api/v1/orders/{$getOrder->getOrderId()}";
            $productionUrlPk   = "https://pandago-api-apso.deliveryhero.io/pk/api/v1/orders/{$getOrder->getOrderId()}";

            // Determine the actual URL to use
            $fullUrl = $sandboxUrl;
            if ('production' === $environment) {
                $fullUrl = ('pk' === $country) ? $productionUrlPk : $productionUrlApac;
            }

            $result = $this->client->orders()->cancel($getOrder->getOrderId(), $cancelRequest);
            // Order is cancelled, no need to clean up
            $this->orderId = null;
            
            $cancelledOrder = $this->client->orders()->get($getOrder->getOrderId());

            return $cancelledOrder;

        } catch (RequestException $e) {
            // The order might be in a state that can't be cancelled
            if ($e->getCode() === 409 && strpos($e->getMessage(), 'Order is not cancellable') !== false) {
                \Log::info('Order is not cancellable - it may have progressed too far');
            } else {
                throw $e;
            }
        }
    }


}
