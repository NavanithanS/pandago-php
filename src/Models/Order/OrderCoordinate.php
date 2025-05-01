<?php
namespace Nava\Pandago\Models\Order;

class OrderCoordinate
{
    /**
     * @var string|null
     */
    protected $clientOrderId;

    /**
     * @var float
     */
    protected $latitude;

    /**
     * @var float
     */
    protected $longitude;

    /**
     * @var int
     */
    protected $updatedAt;

    /**
     * OrderCoordinate constructor.
     *
     * @param float $latitude
     * @param float $longitude
     * @param int $updatedAt
     * @param string|null $clientOrderId
     */
    public function __construct(float $latitude, float $longitude, int $updatedAt, ?string $clientOrderId = null)
    {
        $this->latitude      = $latitude;
        $this->longitude     = $longitude;
        $this->updatedAt     = $updatedAt;
        $this->clientOrderId = $clientOrderId;
    }

    /**
     * Create a new OrderCoordinate instance from an array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['latitude'],
            $data['longitude'],
            $data['updated_at'],
            $data['client_order_id'] ?? null
        );
    }

    /**
     * Get the client order ID.
     *
     * @return string|null
     */
    public function getClientOrderId(): ?string
    {
        return $this->clientOrderId;
    }

    /**
     * Get the latitude.
     *
     * @return float
     */
    public function getLatitude(): float
    {
        return $this->latitude;
    }

    /**
     * Get the longitude.
     *
     * @return float
     */
    public function getLongitude(): float
    {
        return $this->longitude;
    }

    /**
     * Get the updated at timestamp.
     *
     * @return int
     */
    public function getUpdatedAt(): int
    {
        return $this->updatedAt;
    }

    /**
     * Convert the coordinate to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'latitude'   => $this->latitude,
            'longitude'  => $this->longitude,
            'updated_at' => $this->updatedAt,
        ];

        if (null !== $this->clientOrderId) {
            $data['client_order_id'] = $this->clientOrderId;
        }

        return $data;
    }
}
