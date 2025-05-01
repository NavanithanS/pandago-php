<?php
namespace Nava\Pandago\Models\Outlet;

use Nava\Pandago\Exceptions\ValidationException;
use Nava\Pandago\Traits\ValidatesParameters;

class CreateOutletRequest
{
    use ValidatesParameters;

    /**
     * @var string
     */
    protected $name;

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
     * @var string
     */
    protected $city;

    /**
     * @var string
     */
    protected $phoneNumber;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var string|null
     */
    protected $street;

    /**
     * @var string|null
     */
    protected $streetNumber;

    /**
     * @var string|null
     */
    protected $building;

    /**
     * @var string|null
     */
    protected $district;

    /**
     * @var string|null
     */
    protected $postalCode;

    /**
     * @var string|null
     */
    protected $riderInstructions;

    /**
     * @var bool|null
     */
    protected $halal;

    /**
     * @var array|null
     */
    protected $addUsers;

    /**
     * CreateOutletRequest constructor.
     *
     * @param string $name
     * @param string $address
     * @param float $latitude
     * @param float $longitude
     * @param string $city
     * @param string $phoneNumber
     * @param string $currency
     * @param string $locale
     * @param string|null $description
     * @throws ValidationException
     */
    public function __construct(
        string $name,
        string $address,
        float $latitude,
        float $longitude,
        string $city,
        string $phoneNumber,
        string $currency,
        string $locale,
        ?string $description = null
    ) {
        $this->validate([
            'name'        => $name,
            'address'     => $address,
            'latitude'    => $latitude,
            'longitude'   => $longitude,
            'city'        => $city,
            'phoneNumber' => $phoneNumber,
            'currency'    => $currency,
            'locale'      => $locale,
            'description' => $description,
        ], [
            'name'        => 'required|string|max:300',
            'address'     => 'required|string|max:255',
            'latitude'    => 'required|numeric',
            'longitude'   => 'required|numeric',
            'city'        => 'required|string|max:300',
            'phoneNumber' => 'required|string',
            'currency'    => 'required|string',
            'locale'      => 'required|string',
            'description' => 'string',
        ]);

        $this->name        = $name;
        $this->address     = $address;
        $this->latitude    = $latitude;
        $this->longitude   = $longitude;
        $this->city        = $city;
        $this->phoneNumber = $phoneNumber;
        $this->currency    = $currency;
        $this->locale      = $locale;
        $this->description = $description;
    }

    /**
     * Create a new CreateOutletRequest instance from an array.
     *
     * @param array $data
     * @return self
     * @throws ValidationException
     */
    public static function fromArray(array $data): self
    {
        $instance = new self(
            $data['name'],
            $data['address'],
            $data['latitude'],
            $data['longitude'],
            $data['city'],
            $data['phone_number'],
            $data['currency'],
            $data['locale'],
            $data['description'] ?? null
        );

        if (isset($data['street'])) {
            $instance->setStreet($data['street']);
        }

        if (isset($data['street_number'])) {
            $instance->setStreetNumber($data['street_number']);
        }

        if (isset($data['building'])) {
            $instance->setBuilding($data['building']);
        }

        if (isset($data['district'])) {
            $instance->setDistrict($data['district']);
        }

        if (isset($data['postal_code'])) {
            $instance->setPostalCode($data['postal_code']);
        }

        if (isset($data['rider_instructions'])) {
            $instance->setRiderInstructions($data['rider_instructions']);
        }

        if (isset($data['halal'])) {
            $instance->setHalal($data['halal']);
        }

        if (isset($data['add_user'])) {
            $instance->setAddUsers($data['add_user']);
        }

        return $instance;
    }

    /**
     * Set the street.
     *
     * @param string $street
     * @return $this
     */
    public function setStreet(string $street): self
    {
        $this->street = $street;
        return $this;
    }

    /**
     * Set the street number.
     *
     * @param string $streetNumber
     * @return $this
     */
    public function setStreetNumber(string $streetNumber): self
    {
        $this->streetNumber = $streetNumber;
        return $this;
    }

    /**
     * Set the building.
     *
     * @param string $building
     * @return $this
     */
    public function setBuilding(string $building): self
    {
        $this->building = $building;
        return $this;
    }

    /**
     * Set the district.
     *
     * @param string $district
     * @return $this
     */
    public function setDistrict(string $district): self
    {
        $this->district = $district;
        return $this;
    }

    /**
     * Set the postal code.
     *
     * @param string $postalCode
     * @return $this
     */
    public function setPostalCode(string $postalCode): self
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    /**
     * Set the rider instructions.
     *
     * @param string $riderInstructions
     * @return $this
     */
    public function setRiderInstructions(string $riderInstructions): self
    {
        $this->riderInstructions = $riderInstructions;
        return $this;
    }

    /**
     * Set whether the outlet is halal.
     *
     * @param bool $halal
     * @return $this
     */
    public function setHalal(bool $halal): self
    {
        $this->halal = $halal;
        return $this;
    }

    /**
     * Set the users to add.
     *
     * @param array $users
     * @return $this
     */
    public function setAddUsers(array $users): self
    {
        $this->addUsers = $users;
        return $this;
    }

    /**
     * Convert the request to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'name'         => $this->name,
            'address'      => $this->address,
            'latitude'     => $this->latitude,
            'longitude'    => $this->longitude,
            'city'         => $this->city,
            'phone_number' => $this->phoneNumber,
            'currency'     => $this->currency,
            'locale'       => $this->locale,
        ];

        if (null !== $this->description) {
            $data['description'] = $this->description;
        }

        if (null !== $this->street) {
            $data['street'] = $this->street;
        }

        if (null !== $this->streetNumber) {
            $data['street_number'] = $this->streetNumber;
        }

        if (null !== $this->building) {
            $data['building'] = $this->building;
        }

        if (null !== $this->district) {
            $data['district'] = $this->district;
        }

        if (null !== $this->postalCode) {
            $data['postal_code'] = $this->postalCode;
        }

        if (null !== $this->riderInstructions) {
            $data['rider_instructions'] = $this->riderInstructions;
        }

        if (null !== $this->halal) {
            $data['halal'] = $this->halal;
        }

        if (null !== $this->addUsers) {
            $data['add_user'] = $this->addUsers;
        }

        return $data;
    }
}
