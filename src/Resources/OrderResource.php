<?php
namespace Nava\Pandago\Resources;

use Nava\Pandago\Contracts\ClientInterface;
use Nava\Pandago\Exceptions\PandagoException;
use Nava\Pandago\Exceptions\RequestException;
use Nava\Pandago\Models\Order\CancelOrderRequest;
use Nava\Pandago\Models\Order\CreateOrderRequest;
use Nava\Pandago\Models\Order\Order;
use Nava\Pandago\Models\Order\OrderCoordinate;
use Nava\Pandago\Models\Order\UpdateOrderRequest;

class OrderResource
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * OrderResource constructor.
     *
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Create a new order.
     *
     * @param CreateOrderRequest $request
     * @return Order
     * @throws PandagoException
     * @throws RequestException
     */
    public function create(CreateOrderRequest $request): Order
    {
        $response = $this->client->request('POST', '/orders', [
            'json' => $request->toArray(),
        ]);

        return Order::fromArray($response);
    }

    /**
     * Get an order by ID.
     *
     * @param string $orderId
     * @return Order
     * @throws PandagoException
     * @throws RequestException
     */
    public function get(string $orderId): Order
    {
        $response = $this->client->request('GET', "/orders/{$orderId}");

        return Order::fromArray($response);
    }

    /**
     * Update an order.
     *
     * @param string $orderId
     * @param UpdateOrderRequest $request
     * @return Order
     * @throws PandagoException
     * @throws RequestException
     */
    public function update(string $orderId, UpdateOrderRequest $request): Order
    {
        $response = $this->client->request('PUT', "/orders/{$orderId}", [
            'json' => $request->toArray(),
        ]);

        return Order::fromArray($response);
    }

    /**
     * Cancel an order.
     *
     * @param string $orderId
     * @param CancelOrderRequest $request
     * @return bool
     * @throws PandagoException
     * @throws RequestException
     */
    public function cancel(string $orderId, CancelOrderRequest $request): bool
    {
        $this->client->request('DELETE', "/orders/{$orderId}", [
            'json' => $request->toArray(),
        ]);

        return true;
    }

    /**
     * Get the coordinates of a courier for an order.
     *
     * @param string $orderId
     * @return OrderCoordinate
     * @throws PandagoException
     * @throws RequestException
     */
    public function getCoordinates(string $orderId): OrderCoordinate
    {
        $response = $this->client->request('GET', "/orders/{$orderId}/coordinates");

        return OrderCoordinate::fromArray($response);
    }

    /**
     * Get the proof of delivery for an order.
     *
     * @param string $orderId
     * @return string Base64 encoded image
     * @throws PandagoException
     * @throws RequestException
     */
    public function getProofOfDelivery(string $orderId): string
    {
        return $this->client->request('GET', "/orders/proof_of_delivery/{$orderId}");
    }

    /**
     * Get the proof of pickup for an order.
     *
     * @param string $orderId
     * @return string Base64 encoded image
     * @throws PandagoException
     * @throws RequestException
     */
    public function getProofOfPickup(string $orderId): string
    {
        return $this->client->request('GET', "/orders/proof_of_pickup/{$orderId}");
    }

    /**
     * Get the proof of return for an order.
     *
     * @param string $orderId
     * @return string Base64 encoded image
     * @throws PandagoException
     * @throws RequestException
     */
    public function getProofOfReturn(string $orderId): string
    {
        return $this->client->request('GET', "/orders/proof_of_return/{$orderId}");
    }

    /**
     * Estimate the delivery fee for an order.
     *
     * @param CreateOrderRequest $request
     * @return array
     * @throws PandagoException
     * @throws RequestException
     */
    public function estimateFee(CreateOrderRequest $request): array
    {
        return $this->client->request('POST', '/orders/fee', [
            'json' => $request->toArray(),
        ]);
    }

    /**
     * Estimate the delivery time for an order.
     *
     * @param CreateOrderRequest $request
     * @return array
     * @throws PandagoException
     * @throws RequestException
     */
    public function estimateTime(CreateOrderRequest $request): array
    {
        return $this->client->request('POST', '/orders/time', [
            'json' => $request->toArray(),
        ]);
    }
}
