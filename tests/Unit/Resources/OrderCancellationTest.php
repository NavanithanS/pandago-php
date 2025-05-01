<?php
namespace Nava\Pandago\Tests\Unit\Resources;

use Mockery;
use Nava\Pandago\Contracts\ClientInterface;
use Nava\Pandago\Exceptions\RequestException;
use Nava\Pandago\Models\Order\CancelOrderRequest;
use Nava\Pandago\Resources\OrderResource;
use Nava\Pandago\Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    /**
     * Test successful order cancellation.
     *
     * @return void
     */
    public function testCancelWithSuccess()
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->with('DELETE', '/orders/y0ud-000001', Mockery::type('array'))
            ->andReturn([]);

        $orderResource = new OrderResource($client);
        $request       = new CancelOrderRequest('MISTAKE_ERROR');

        $result = $orderResource->cancel('y0ud-000001', $request);

        $this->assertTrue($result);
    }

    /**
     * Test cancellation of a non-existent order.
     *
     * @return void
     */
    public function testCancelWithNotFound()
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->with('DELETE', '/orders/y0ud-000001', Mockery::type('array'))
            ->andThrow(new RequestException('Order not found', 404));

        $orderResource = new OrderResource($client);
        $request       = new CancelOrderRequest('MISTAKE_ERROR');

        $this->expectException(RequestException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('Order not found');

        $orderResource->cancel('y0ud-000001', $request);
    }

    /**
     * Test cancellation of an order that cannot be cancelled.
     *
     * @return void
     */
    public function testCancelWithUncancellableOrder()
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->with('DELETE', '/orders/y0ud-000001', Mockery::type('array'))
            ->andThrow(new RequestException('Order is not cancellable', 409));

        $orderResource = new OrderResource($client);
        $request       = new CancelOrderRequest('MISTAKE_ERROR');

        $this->expectException(RequestException::class);
        $this->expectExceptionCode(409);
        $this->expectExceptionMessage('Order is not cancellable');

        $orderResource->cancel('y0ud-000001', $request);
    }

    /**
     * Test cancellation with a server error response.
     *
     * @return void
     */
    public function testCancelWithServerError()
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->with('DELETE', '/orders/y0ud-000001', Mockery::type('array'))
            ->andThrow(new RequestException('Unable to proceed, something went wrong', 500));

        $orderResource = new OrderResource($client);
        $request       = new CancelOrderRequest('MISTAKE_ERROR');

        $this->expectException(RequestException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('Unable to proceed, something went wrong');

        $orderResource->cancel('y0ud-000001', $request);
    }

    /**
     * Test cancellation with all valid cancellation reasons.
     *
     * @return void
     */
    public function testCancelWithAllValidReasons()
    {
        $validReasons = CancelOrderRequest::getValidReasons();

        foreach ($validReasons as $reason) {
            $client = Mockery::mock(ClientInterface::class);
            $client->shouldReceive('request')
                ->once()
                ->with('DELETE', '/orders/y0ud-000001', Mockery::on(function ($options) use ($reason) {
                    $data = $options['json'] ?? [];
                    return isset($data['reason']) && $data['reason'] === $reason;
                }))
                ->andReturn([]);

            $orderResource = new OrderResource($client);
            $request       = new CancelOrderRequest($reason);

            $result = $orderResource->cancel('y0ud-000001', $request);

            $this->assertTrue($result, "Failed to cancel order with reason: {$reason}");
        }
    }

    /**
     * Teardown after tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
