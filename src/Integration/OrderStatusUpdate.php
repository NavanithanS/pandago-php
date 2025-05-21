<?php
namespace Nava\Pandago\Integration;

use Illuminate\Http\Request;
use Nava\Pandago\Client;
use Nava\Pandago\Config;
use Nava\Pandago\Exceptions\RequestException;
use Nava\Pandago\Models\Order\CancelOrderRequest;
use Nava\Pandago\Models\Order\CreateOrderRequest;
use Nava\Pandago\Models\Order\Order;

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

    public function getOrder(Order $order)
    {
        try{
            $orderId = $order->getOrderId();
            $clientOrderId = $order->getClientOrderId();

            $checkedOrder   = $this->client->orders()->get($orderId);

            $checkedOrderStatus = $checkedOrder->getStatus();

            $checkedOrderDetails = $checkedOrder->toArray();

            // Verify the order status and other key fields
            $this->assertNotEmpty($checkedOrder->getStatus(), 'Order status should not be empty');
            $this->assertEquals($orderId, $checkedOrder->getOrderId(), 'Order ID should match');
            $this->assertEquals($clientOrderId, $checkedOrder->getClientOrderId(), 'Client Order ID should match');

            // Wait a few seconds to check if status has changed (sandbox might update it automatically)
            sleep(5);

            //second times status check 
            $updatedOrder = $this->client->orders()->get($orderId);

            if($updatedOrder->getStatus() !== $checkedOrder->getStatus()){
                return $updatedOrder;
            }else{
                return $checkedOrder;
            }
            
        }catch (RequestException $e){
             // Handle common errors
            if ($e->getCode() === 404 && strpos($e->getMessage(), 'Outlet not found') !== false) {
                $this->markTestSkipped('Outlet not found. Please configure a valid client vendor ID.');
            } elseif ($e->getCode() === 422 && strpos($e->getMessage(), 'No Branch found') !== false) {
                $this->markTestSkipped('No branch found close to sender coordinates');
            } else {
                throw $e;
            }
        }
    }
    
}
