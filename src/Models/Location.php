<?php
namespace Nava\Pandago\Models;

use Nava\Pandago\Exceptions\ValidationException;
use Nava\Pandago\Traits\ValidatesParameters;

class Location
{
    use ValidatesParameters;

    /**
     * @var string
     */
    protected $address;

    /**
     * @var float
     */
    protected $latitude;

    /**
     * @var float
     */
    protected $longitude;

    /**
     * @var string|null
     */
    protected $postalCode;

    /**
     * Location constructor.
     *
     * @param string $address
     * @param float $latitude
     * @param float $longitude
     * @param string|null $postalCode
     * @throws ValidationException
     */
    public function __construct(string $address, float $latitude, float $longitude, ?string $postalCode = null)
    {
        $this->validate([
            'address'    => $address,
            'latitude'   => $latitude,
            'longitude'  => $longitude,
            'postalCode' => $postalCode,
        ], [
            'address'    => 'required|string|max:255',
            'latitude'   => 'required|numeric',
            'longitude'  => 'required|numeric',
            'postalCode' => 'string|max:120',
        ]);

        $this->address    = $address;
        $this->latitude   = $latitude;
        $this->longitude  = $longitude;
        $this->postalCode = $postalCode;
    }

    /**
     * Create a new Location instance from an array.
     *
     * @param array $data
     * @return self
     * @throws ValidationException
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['address'],
            $data['latitude'],
            $data['longitude'],
            $data['postalcode'] ?? null
        );
    }

    /**
     * Get the address.
     *
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
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
     * Get the postal code.
     *
     * @return string|null
     */
    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    /**
     * Convert the location to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'address'   => $this->address,
            'latitude'  => $this->latitude,
            'longitude' => $this->longitude,
        ];

        if (null !== $this->postalCode) {
            $data['postalcode'] = $this->postalCode;
        }

        return $data;
    }
}
