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
     * Get the full API URL for a given path.
     *
     * @param string $path
     * @return string
     */
    private function getFullUrl(string $path): string
    {
        $baseUrl = $this->client->getConfig()->getApiBaseUrl();
        return rtrim($baseUrl, '/') . $path;
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
        $response = $this->client->request('POST', $this->getFullUrl('/orders'), [
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
        $response = $this->client->request('GET', $this->getFullUrl("/orders/{$orderId}"));

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
        $response = $this->client->request('PUT', $this->getFullUrl("/orders/{$orderId}"), [
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
        $this->client->request('DELETE', $this->getFullUrl("/orders/{$orderId}"), [
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
        $response = $this->client->request('GET', $this->getFullUrl("/orders/{$orderId}/coordinates"));

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
        $response = $this->client->request('GET', $this->getFullUrl("/orders/proof_of_delivery/{$orderId}"));

        // Handle different response formats
        if (is_array($response)) {
            if (isset($response['data'])) {
                return $response['data'];
            }

            // If it's an array without a 'data' key, it's not the expected format
            throw new PandagoException('Unexpected response format for proof of delivery');
        }

        // If it's already a string, return it directly
        return $response;
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
        $response = $this->client->request('GET', $this->getFullUrl("/orders/proof_of_pickup/{$orderId}"));

        // Handle different response formats
        if (is_array($response)) {
            if (isset($response['data'])) {
                return $response['data'];
            }

            // If it's an array without a 'data' key, it's not the expected format
            throw new PandagoException('Unexpected response format for proof of pickup');
        }

        // If it's already a string, return it directly
        return $response;
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
        $response = $this->client->request('GET', $this->getFullUrl("/orders/proof_of_return/{$orderId}"));

        // Handle different response formats
        if (is_array($response)) {
            if (isset($response['data'])) {
                return $response['data'];
            }

            // If it's an array without a 'data' key, it's not the expected format
            throw new PandagoException('Unexpected response format for proof of return');
        }

        // If it's already a string, return it directly
        return $response;
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
        return $this->client->request('POST', $this->getFullUrl('/orders/fee'), [
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
        return $this->client->request('POST', $this->getFullUrl('/orders/time'), [
            'json' => $request->toArray(),
        ]);
    }
}
