<?php
namespace Nava\Pandago\Tests\Integration;

use Nava\Pandago\Client;
use Nava\Pandago\Exceptions\RequestException;
use Nava\Pandago\Models\Contact;
use Nava\Pandago\Models\Location;
use Nava\Pandago\Models\Order\CancelOrderRequest;
use Nava\Pandago\Models\Order\CreateOrderRequest;
use Nava\Pandago\Models\Order\Order;
use Nava\Pandago\Tests\TestCase;

class OrderCancellationIntegrationTest extends TestCase
{
    /**
     * @var \Nava\Pandago\PandagoClient
     */
    protected $client;

    /**
     * @var string|null
     */
    protected $testOrderId;

    /**
     * Setup before tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Skip integration tests if required config values are missing
        if (! $this->checkRequiredConfig(['client_id', 'key_id', 'scope'])) {
            $this->markTestSkipped(
                'Integration tests require API credentials. Set them in tests/config.php to run the tests.'
            );
        }

        $this->client = Client::fromArray($this->getConfig());
    }

    /**
     * Teardown after tests.
     *
     * @return void
     */
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

    /**
     * Test cancelling orders with different valid reasons.
     *
     * @return void
     */
    public function testCancelOrderWithDifferentReasons()
    {
        // Test will try to cancel with each valid reason
        $validReasons = CancelOrderRequest::getValidReasons();

        foreach ($validReasons as $reason) {
            // Create an order to cancel
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
                $order             = $this->client->orders()->create($request);
                $this->testOrderId = $order->getOrderId();

                $this->assertInstanceOf(Order::class, $order);
                $this->assertNotEmpty($order->getOrderId());

                // Test cancellation with the current reason
                $cancelRequest = new CancelOrderRequest($reason);
                $result        = $this->client->orders()->cancel($order->getOrderId(), $cancelRequest);

                $this->assertTrue($result, "Failed to cancel order with reason: {$reason}");

                // Order is cancelled, no need to clean up for this iteration
                $this->testOrderId = null;

            } catch (RequestException $e) {
                // Handle common integration test failures
                if ($e->getCode() === 404 && strpos($e->getMessage(), 'Outlet not found') !== false) {
                    $this->markTestSkipped('Integration test failed: Outlet not found. Please configure a valid client vendor ID.');
                } elseif ($e->getCode() === 422 && strpos($e->getMessage(), 'No Branch found') !== false) {
                    $this->markTestSkipped('Integration test failed: No branch found that is close enough to the given sender coordinates.');
                } elseif ($e->getCode() === 409 && strpos($e->getMessage(), 'Order is not cancellable') !== false) {
                    // This is expected in some cases, so we'll continue
                    $this->testOrderId = null;
                    continue;
                } else {
                    throw $e;
                }
            }
        }
    }

    /**
     * Test cancelling a non-existent order.
     *
     * @return void
     */
    public function testCancelNonExistentOrder()
    {
        $cancelRequest = new CancelOrderRequest('MISTAKE_ERROR');

        try {
            $this->client->orders()->cancel('non-existent-order-id', $cancelRequest);
            $this->fail('Exception was not thrown for non-existent order');
        } catch (RequestException $e) {
            $this->assertEquals(404, $e->getCode());
            $this->assertStringContainsString('not found', strtolower($e->getMessage()));
        }
    }

    /**
     * Test creating an order and attempting to cancel it multiple times.
     *
     * @return void
     */
    public function testCancelOrderMultipleTimes()
    {
        // Create an order to cancel
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
            $order             = $this->client->orders()->create($request);
            $this->testOrderId = $order->getOrderId();

            $this->assertInstanceOf(Order::class, $order);
            $this->assertNotEmpty($order->getOrderId());

            // First cancellation should succeed
            $cancelRequest = new CancelOrderRequest('MISTAKE_ERROR');
            $result        = $this->client->orders()->cancel($order->getOrderId(), $cancelRequest);
            $this->assertTrue($result);

            // Order is now cancelled, attempting to cancel again should fail
            try {
                $this->client->orders()->cancel($order->getOrderId(), $cancelRequest);
                $this->fail('Exception was not thrown for already cancelled order');
            } catch (RequestException $e) {
                // This is expected
                $this->assertContains($e->getCode(), [404, 409]);
            }

            // Order is cancelled, no need to clean up
            $this->testOrderId = null;

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
