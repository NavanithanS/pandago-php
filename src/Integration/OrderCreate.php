<?php
namespace Nava\Pandago\Integration;

use Illuminate\Http\Request;
use Nava\Pandago\Client;
use Nava\Pandago\Config;
use Nava\Pandago\Exceptions\RequestException;
use Nava\Pandago\Models\Order\CancelOrderRequest;
use Nava\Pandago\Models\Order\CreateOrderRequest;
use Nava\Pandago\Models\Order\Order;

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

        public function createOrder(Request $request)
    {
        $data = PandagoAddress::prepareData($request);

        // Create recipient
        $recipient = PandagoAddress::getCustomerContact($data);

        // Create order request with a client order ID for tracing
        $clientOrderId = PandagoAddress::generateClientOrderId('create');

        $orderRequest = new CreateOrderRequest(
            $recipient,
            $data['subtotal'],
            'Creating Order',
        );

        $orderRequest->setClientOrderId($clientOrderId);

        //set sender 
        $store = Store::find($data['store_id']);
        // \Log::info('Store: ' . $store->postcode);
        $sender = PandagoAddress::getOutletContact($store);
        $orderRequest->setSender($sender);
        $orderRequest->setPaymentMethod('PAID');
        $orderRequest->setColdbagNeeded(true);

        //for print to check the data
        $requestOrderPayload = $orderRequest->toArray();

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

            // Verify basic order details
            $this->assertNotEmpty($order->getOrderId(), 'Order ID should not be empty');
            $this->assertEquals($clientOrderId, $order->getClientOrderId(), 'Client Order ID should match');

            // Verify expected status (typically 'NEW' for a newly created order)
            $this->assertEquals('NEW', $order->getStatus(), 'Order status should be NEW');

            // Verify amount
            $this->assertEquals($data['subtotal'], $order->getAmount(), 'Order amount should match');

            // Verify cold bag setting
            $this->assertTrue($order->isColdbagNeeded(), 'Cold bag needed should be true');

            // Verify description
            $this->assertEquals('Creating Order', $order->getDescription(), 'Description should match');

            // Verify recipient details using TestAddresses constants
            $orderRecipient = $order->getRecipient();
            $this->assertInstanceOf(Contact::class, $orderRecipient);

            //TO DO: NOT SURE 
            $this->assertEquals($recipient->name, $orderRecipient->getName(), 'Recipient name should match');
            $this->assertEquals($recipient->phoneNumber, $orderRecipient->getPhoneNumber(), 'Recipient phone should match');

            // Verify recipient location
            $recipientLocation = $orderRecipient->getLocation();
            $this->assertInstanceOf(Location::class, $recipientLocation);

            //TO DO: NOT SURE 
            $this->assertEquals($sender->address, $recipientLocation->getAddress(), 'Recipient address should match');

            try {
                $retrievedOrder = $this->client->orders()->get($order->getOrderId());

                // Verify retrieved order matches the created one
                $this->assertEquals($order->getOrderId(), $retrievedOrder->getOrderId(), 'Order IDs should match');
                $this->assertEquals($order->getClientOrderId(), $retrievedOrder->getClientOrderId(), 'Client Order IDs should match');

            } catch (RequestException $e) {
                echo "Could not retrieve the created order, but this doesn't mean it wasn't created:\n";
                echo "• Error: " . $e->getMessage() . "\n";
            }

            return $order;

        } catch (RequestException $e) {
            // Handle common integration test failures
            if ($e->getCode() === 404 && strpos($e->getMessage(), 'Outlet not found') !== false) {
                $this->markTestSkipped('Integration test failed: Outlet not found. Please configure a valid client vendor ID.');
            } elseif ($e->getCode() === 422 && strpos($e->getMessage(), 'No Branch found') !== false) {
                $this->markTestSkipped('Integration test failed: No branch found that is close enough to the given sender coordinates.');
            } else {
                throw $e;
            }
        }

    }
}
