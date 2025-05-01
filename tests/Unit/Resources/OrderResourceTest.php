<?php
namespace Nava\Pandago\Tests\Unit\Resources;

use Mockery;
use Nava\Pandago\Contracts\ClientInterface;
use Nava\Pandago\Models\Contact;
use Nava\Pandago\Models\Location;
use Nava\Pandago\Models\Order\CancelOrderRequest;
use Nava\Pandago\Models\Order\CreateOrderRequest;
use Nava\Pandago\Models\Order\Order;
use Nava\Pandago\Models\Order\OrderCoordinate;
use Nava\Pandago\Models\Order\UpdateOrderRequest;
use Nava\Pandago\Resources\OrderResource;
use Nava\Pandago\Tests\TestCase;

class OrderResourceTest extends TestCase
{
    public function testCreate()
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->with('POST', '/orders', Mockery::type('array'))
            ->andReturn([
                'order_id'        => 'y0ud-000001',
                'client_order_id' => 'client-ref-000001',
                'status'          => 'NEW',
            ]);

        $orderResource = new OrderResource($client);

        $location  = new Location('670, Era Jaya', 7.3500280, 100.4374034);
        $recipient = new Contact('Chalit', '+60125918131', $location);
        $request   = new CreateOrderRequest($recipient, 349.50, 'Woodford Reserve Kentucky Bourbon');

        $order = $orderResource->create($request);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('y0ud-000001', $order->getOrderId());
        $this->assertEquals('client-ref-000001', $order->getClientOrderId());
        $this->assertEquals('NEW', $order->getStatus());
    }

    public function testGet()
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->with('GET', '/orders/y0ud-000001')
            ->andReturn([
                'order_id'        => 'y0ud-000001',
                'client_order_id' => 'client-ref-000001',
                'status'          => 'DELIVERED',
            ]);

        $orderResource = new OrderResource($client);

        $order = $orderResource->get('y0ud-000001');

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('y0ud-000001', $order->getOrderId());
        $this->assertEquals('client-ref-000001', $order->getClientOrderId());
        $this->assertEquals('DELIVERED', $order->getStatus());
    }

    public function testUpdate()
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->with('PUT', '/orders/y0ud-000001', Mockery::type('array'))
            ->andReturn([
                'order_id' => 'y0ud-000001',
                'status'   => 'NEW',
            ]);

        $orderResource = new OrderResource($client);

        $request = new UpdateOrderRequest();
        $request->setAmount(25.0);

        $order = $orderResource->update('y0ud-000001', $request);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('y0ud-000001', $order->getOrderId());
        $this->assertEquals('NEW', $order->getStatus());
    }

    public function testCancel()
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->with('DELETE', '/orders/y0ud-000001', Mockery::type('array'))
            ->andReturn([]);

        $orderResource = new OrderResource($client);

        $request = new CancelOrderRequest('MISTAKE_ERROR');

        $result = $orderResource->cancel('y0ud-000001', $request);

        $this->assertTrue($result);
    }

    public function testGetCoordinates()
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->with('GET', '/orders/y0ud-000001/coordinates')
            ->andReturn([
                'client_order_id' => 'client-ref-000001',
                'latitude'        => 7.3500280,
                'longitude'       => 100.4374034,
                'updated_at'      => 1536802252,
            ]);

        $orderResource = new OrderResource($client);

        $coordinates = $orderResource->getCoordinates('y0ud-000001');

        $this->assertInstanceOf(OrderCoordinate::class, $coordinates);
        $this->assertEquals('client-ref-000001', $coordinates->getClientOrderId());
        $this->assertEquals(7.3500280, $coordinates->getLatitude());
        $this->assertEquals(100.4374034, $coordinates->getLongitude());
        $this->assertEquals(1536802252, $coordinates->getUpdatedAt());
    }

    public function testEstimateFee()
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->with('POST', '/orders/fee', Mockery::type('array'))
            ->andReturn([
                'client_order_id'        => 'client-ref-000001',
                'estimated_delivery_fee' => 8.17,
            ]);

        $orderResource = new OrderResource($client);

        $location  = new Location('670, Era Jaya', 7.3500280, 100.4374034);
        $recipient = new Contact('Chalit', '+60125918131', $location);
        $request   = new CreateOrderRequest($recipient, 349.50, 'Woodford Reserve Kentucky Bourbon');

        $result = $orderResource->estimateFee($request);

        $this->assertEquals('client-ref-000001', $result['client_order_id']);
        $this->assertEquals(8.17, $result['estimated_delivery_fee']);
    }

    public function testEstimateTime()
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->with('POST', '/orders/time', Mockery::type('array'))
            ->andReturn([
                'client_order_id'         => 'client-ref-000001',
                'estimated_pickup_time'   => '2025-05-01T01:11:23.123Z',
                'estimated_delivery_time' => '2025-05-01T01:13:37.123Z',
            ]);

        $orderResource = new OrderResource($client);

        $location  = new Location('670, Era Jaya', 7.3500280, 100.4374034);
        $recipient = new Contact('Chalit', '+60125918131', $location);
        $request   = new CreateOrderRequest($recipient, 349.50, 'Woodford Reserve Kentucky Bourbon');

        $result = $orderResource->estimateTime($request);

        $this->assertEquals('client-ref-000001', $result['client_order_id']);
        $this->assertEquals('2025-05-01T01:11:23.123Z', $result['estimated_pickup_time']);
        $this->assertEquals('2025-05-01T01:13:37.123Z', $result['estimated_delivery_time']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
