<?php
namespace Nava\Pandago\Integration;

use Illuminate\Http\Request;
use Nava\Pandago\Client;
use Nava\Pandago\Config;
use Nava\Pandago\Exceptions\RequestException;
use Nava\Pandago\Models\Order\CancelOrderRequest;
use Nava\Pandago\Models\Order\CreateOrderRequest;
use Nava\Pandago\Models\Order\Order;
use Nava\Pandago\PandagoClient;
use Log;

class OrderStatusUpdate 
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

    public function getOrder($order)
    {
        try{
            $orderId = $order['order_id'];

            // $clientOrderId = $order['client_order_id'];

            $checkedOrder   = $this->client->orders()->get($orderId);

            $checkedOrderStatus = $checkedOrder->getStatus();

            $checkedOrderDetails = $checkedOrder->toArray();

            // Wait a few seconds to check if status has changed (sandbox might update it automatically)
            sleep(5);

            //second times status check 
            $updatedOrder = $this->client->orders()->get($orderId);

            if($updatedOrder->getStatus() !== $checkedOrder->getStatus()){
                return $updatedOrder;
            }else{
                // \Log::info('Checked Order: ' . print_r($checkedOrder, true));
                // die();
                return $checkedOrder;
            }
            
        }catch (RequestException $e){
             // Handle common errors
            if ($e->getCode() === 404 && strpos($e->getMessage(), 'Outlet not found') !== false) {
                Log::info('Outlet not found. Please configure a valid client vendor ID.');
            } elseif ($e->getCode() === 422 && strpos($e->getMessage(), 'No Branch found') !== false) {
                Log::info('No branch found close to sender coordinates');
            } else {
                throw $e;
            }
        }
    }
    
}
