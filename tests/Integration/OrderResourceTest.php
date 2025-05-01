<?php
namespace Nava\Pandago\Tests\Integration;

use Nava\Pandago\Client;
use Nava\Pandago\Exceptions\RequestException;
use Nava\Pandago\Models\Contact;
use Nava\Pandago\Models\Location;
use Nava\Pandago\Models\Order\CancelOrderRequest;
use Nava\Pandago\Models\Order\CreateOrderRequest;
use Nava\Pandago\Models\Order\Order;
use Nava\Pandago\Models\Order\UpdateOrderRequest;
use Nava\Pandago\Tests\TestCase;

class OrderResourceTest extends TestCase
{
    /**
     * @var \Nava\Pandago\PandagoClient
     */
    protected $client;

    /**
     * @var string|null
     */
    protected $testOrderId;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip integration tests if no API credentials are provided
        if (empty(getenv('PANDAGO_CLIENT_ID')) || empty(getenv('PANDAGO_KEY_ID')) || empty(getenv('PANDAGO_SCOPE'))) {
            $this->markTestSkipped('Integration tests require API credentials. Set the PANDAGO_CLIENT_ID, PANDAGO_KEY_ID, and PANDAGO_SCOPE environment variables to run them.');
        }

        $this->client = Client::fromArray($this->getConfig());
    }

    protected function tearDown(): void
    {
        // If a test order was created and not cancelled, attempt to cancel it
        if ($this->testOrderId) {
            try {
                $this->client->orders()->cancel(
                    $this->testOrderId,
                    new CancelOrderRequest('MISTAKE_ERROR')
                );
            } catch (\Exception $e) {
                // Ignore any errors during cleanup
            }

            $this->testOrderId = null;
        }

        parent::tearDown();
    }

    public function testCreateOrder()
    {
        // Create recipient
        $location = new Location(
            '670, Era Jaya',
            7.3500280,
            100.4374034
        );
        $recipient = new Contact('Chalit', '+60125918131', $location);

        // Create request
        $request = new CreateOrderRequest(
            $recipient,
            349.50,
            'Woodford Reserve Kentucky Bourbon'
        );

        // Set client order ID to make it easier to track
        $clientOrderId = 'test-' . uniqid();
        $request->setClientOrderId($clientOrderId);

        // Set sender (using client vendor ID)
        // Note: This requires a valid client vendor ID to be configured in your test environment
        $clientVendorId = getenv('PANDAGO_TEST_CLIENT_VENDOR_ID');
        if ($clientVendorId) {
            $request->setClientVendorId($clientVendorId);
        } else {
            // If no client vendor ID is provided, set a sender
            $senderLocation = new Location(
                '8, Jalan Laguna 1',
                5.3731476,
                100.4068053
            );
            $sender = new Contact(
                'GuangYou',
                '+601110550716',
                $senderLocation,
                'use the left side door'
            );
            $request->setSender($sender);
        }

        // Set additional options
        $request->setColdbagNeeded(true);

        try {
            $order = $this->client->orders()->create($request);

            // Store order ID for cleanup
            $this->testOrderId = $order->getOrderId();

            $this->assertInstanceOf(Order::class, $order);
            $this->assertNotEmpty($order->getOrderId());
            $this->assertEquals($clientOrderId, $order->getClientOrderId());
            $this->assertEquals('PAID', $order->getPaymentMethod());
            $this->assertEquals(349.50, $order->getAmount());
            $this->assertTrue($order->isColdbagNeeded());

            // Assert sender is set correctly if using client vendor ID
            if ($clientVendorId) {
                $this->assertEquals($clientVendorId, $order->getClientVendorId());
            } else {
                $sender = $order->getSender();
                $this->assertInstanceOf(Contact::class, $sender);
                $this->assertEquals('GuangYou', $sender->getName());
            }

            // Verify recipient
            $orderRecipient = $order->getRecipient();
            $this->assertInstanceOf(Contact::class, $orderRecipient);
            $this->assertEquals('Chalit', $orderRecipient->getName());
            $this->assertEquals('+60125918131', $orderRecipient->getPhoneNumber());

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

    /**
     * @depends testCreateOrder
     */
    public function testGetOrder(Order $createdOrder)
    {
        $orderId = $createdOrder->getOrderId();
        $order   = $this->client->orders()->get($orderId);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($orderId, $order->getOrderId());
        $this->assertEquals($createdOrder->getClientOrderId(), $order->getClientOrderId());

        return $order;
    }

    /**
     * @depends testGetOrder
     */
    public function testUpdateOrder(Order $order)
    {
        $orderId = $order->getOrderId();

        $request = new UpdateOrderRequest();
        $request->setDescription('Updated test description');

        try {
            $updatedOrder = $this->client->orders()->update($orderId, $request);

            $this->assertInstanceOf(Order::class, $updatedOrder);
            $this->assertEquals($orderId, $updatedOrder->getOrderId());
            $this->assertEquals('Updated test description', $updatedOrder->getDescription());

            return $updatedOrder;
        } catch (RequestException $e) {
            // Some countries/environments don't allow order updates
            if ($e->getCode() === 405 && strpos($e->getMessage(), 'order update is not allowed for this country') !== false) {
                $this->markTestSkipped('Order updates are not allowed for this country');
            } elseif ($e->getCode() === 422 && strpos($e->getMessage(), 'order can not be updated') !== false) {
                $this->markTestSkipped('Order cannot be updated - it may be in a state that doesn\'t allow updates');
            } else {
                throw $e;
            }
        }
    }

    /**
     * @depends testCreateOrder
     */
    public function testGetCoordinates(Order $order)
    {
        try {
            $coordinates = $this->client->orders()->getCoordinates($order->getOrderId());

            $this->assertNotNull($coordinates->getLatitude());
            $this->assertNotNull($coordinates->getLongitude());
            $this->assertNotNull($coordinates->getUpdatedAt());

        } catch (RequestException $e) {
            // Coordinates might not be available yet for newly created orders
            if ($e->getCode() === 404) {
                $this->markTestSkipped('Coordinates not available for this order yet');
            } else {
                throw $e;
            }
        }
    }

    public function testEstimateFee()
    {
        // Create request for fee estimation
        $location = new Location(
            '670, Era Jaya',
            7.3500280,
            100.4374034
        );
        $recipient = new Contact('Chalit', '+60125918131', $location);

        $request = new CreateOrderRequest(
            $recipient,
            349.50,
            'Woodford Reserve Kentucky Bourbon'
        );

        // Set sender
        $senderLocation = new Location(
            '8, Jalan Laguna 1',
            5.3731476,
            100.4068053
        );
        $sender = new Contact(
            'GuangYou',
            '+601110550716',
            $senderLocation
        );
        $request->setSender($sender);

        try {
            $result = $this->client->orders()->estimateFee($request);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('estimated_delivery_fee', $result);
            $this->assertIsNumeric($result['estimated_delivery_fee']);

        } catch (RequestException $e) {
            // Skip if the API doesn't support this feature for the test environment
            $this->markTestSkipped('Fee estimation failed: ' . $e->getMessage());
        }
    }

    public function testEstimateTime()
    {
        // Create request for time estimation
        $location = new Location(
            '670, Era Jaya',
            7.3500280,
            100.4374034
        );
        $recipient = new Contact('Chalit', '+60125918131', $location);

        $request = new CreateOrderRequest(
            $recipient,
            349.50,
            'Woodford Reserve Kentucky Bourbon'
        );

        // Set sender
        $senderLocation = new Location(
            '8, Jalan Laguna 1',
            5.3731476,
            100.4068053
        );
        $sender = new Contact(
            'GuangYou',
            '+601110550716',
            $senderLocation
        );
        $request->setSender($sender);

        try {
            $result = $this->client->orders()->estimateTime($request);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('estimated_pickup_time', $result);
            $this->assertArrayHasKey('estimated_delivery_time', $result);

        } catch (RequestException $e) {
            // Skip if the API doesn't support this feature for the test environment
            $this->markTestSkipped('Time estimation failed: ' . $e->getMessage());
        }
    }

    /**
     * @depends testUpdateOrder
     */
    public function testCancelOrder(Order $order)
    {
        $orderId = $order->getOrderId();

        $request = new CancelOrderRequest('MISTAKE_ERROR');

        try {
            $result = $this->client->orders()->cancel($orderId, $request);

            $this->assertTrue($result);

            // Order is cancelled, no need to clean up
            $this->testOrderId = null;

        } catch (RequestException $e) {
            // The order might be in a state that can't be cancelled
            if ($e->getCode() === 409 && strpos($e->getMessage(), 'Order is not cancellable') !== false) {
                $this->markTestSkipped('Order is not cancellable - it may have progressed too far');
            } else {
                throw $e;
            }
        }
    }
}
