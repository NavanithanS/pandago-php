<?php
namespace Nava\Pandago\Models;

class Timeline
{
    /**
     * @var string|null
     */
    protected $estimatedPickupTime;

    /**
     * @var string|null
     */
    protected $estimatedDeliveryTime;

    /**
     * Timeline constructor.
     *
     * @param string|null $estimatedPickupTime
     * @param string|null $estimatedDeliveryTime
     */
    public function __construct(?string $estimatedPickupTime = null, ?string $estimatedDeliveryTime = null)
    {
        $this->estimatedPickupTime   = $estimatedPickupTime;
        $this->estimatedDeliveryTime = $estimatedDeliveryTime;
    }

    /**
     * Create a new Timeline instance from an array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['estimated_pickup_time'] ?? null,
            $data['estimated_delivery_time'] ?? null
        );
    }

    /**
     * Get the estimated pickup time.
     *
     * @return string|null
     */
    public function getEstimatedPickupTime(): ?string
    {
        return $this->estimatedPickupTime;
    }

    /**
     * Get the estimated delivery time.
     *
     * @return string|null
     */
    public function getEstimatedDeliveryTime(): ?string
    {
        return $this->estimatedDeliveryTime;
    }

    /**
     * Convert the timeline to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'estimated_pickup_time'   => $this->estimatedPickupTime,
            'estimated_delivery_time' => $this->estimatedDeliveryTime,
        ];
    }
}
