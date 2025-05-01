<?php
namespace Nava\Pandago\Models\Outlet;

use Nava\Pandago\Traits\ValidatesParameters;

class Outlet
{
    use ValidatesParameters;

    /**
     * @var string|null
     */
    protected $vendorId;

    /**
     * @var string|null
     */
    protected $clientVendorId;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $address;

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
     * @var string|null
     */
    protected $description;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var bool|null
     */
    protected $halal;

    /**
     * @var array|null
     */
    protected $users;

    /**
     * @var array|null
     */
    protected $addUser;

    /**
     * @var array|null
     */
    protected $deleteUser;

    /**
     * Create a new Outlet instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Create a new Outlet instance from an array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Fill the outlet with attributes.
     *
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes): self
    {
        if (isset($attributes['vendor_id'])) {
            $this->vendorId = $attributes['vendor_id'];
        }

        if (isset($attributes['client_vendor_id'])) {
            $this->clientVendorId = $attributes['client_vendor_id'];
        }

        if (isset($attributes['name'])) {
            $this->name = $attributes['name'];
        }

        if (isset($attributes['address'])) {
            $this->address = $attributes['address'];
        }

        if (isset($attributes['street'])) {
            $this->street = $attributes['street'];
        }

        if (isset($attributes['street_number'])) {
            $this->streetNumber = $attributes['street_number'];
        }

        if (isset($attributes['building'])) {
            $this->building = $attributes['building'];
        }

        if (isset($attributes['district'])) {
            $this->district = $attributes['district'];
        }

        if (isset($attributes['postal_code'])) {
            $this->postalCode = $attributes['postal_code'];
        }

        if (isset($attributes['rider_instructions'])) {
            $this->riderInstructions = $attributes['rider_instructions'];
        }

        if (isset($attributes['latitude'])) {
            $this->latitude = (float) $attributes['latitude'];
        }

        if (isset($attributes['longitude'])) {
            $this->longitude = (float) $attributes['longitude'];
        }

        if (isset($attributes['city'])) {
            $this->city = $attributes['city'];
        }

        if (isset($attributes['phone_number'])) {
            $this->phoneNumber = $attributes['phone_number'];
        }

        if (isset($attributes['currency'])) {
            $this->currency = $attributes['currency'];
        }

        if (isset($attributes['description'])) {
            $this->description = $attributes['description'];
        }

        if (isset($attributes['locale'])) {
            $this->locale = $attributes['locale'];
        }

        if (isset($attributes['halal'])) {
            $this->halal = (bool) $attributes['halal'];
        }

        if (isset($attributes['users'])) {
            $this->users = $attributes['users'];
        }

        if (isset($attributes['add_user'])) {
            $this->addUser = $attributes['add_user'];
        }

        if (isset($attributes['delete_user'])) {
            $this->deleteUser = $attributes['delete_user'];
        }

        return $this;
    }

    /**
     * Get the vendor ID.
     *
     * @return string|null
     */
    public function getVendorId(): ?string
    {
        return $this->vendorId;
    }

    /**
     * Get the client vendor ID.
     *
     * @return string|null
     */
    public function getClientVendorId(): ?string
    {
        return $this->clientVendorId;
    }

    /**
     * Set the client vendor ID.
     *
     * @param string $clientVendorId
     * @return $this
     */
    public function setClientVendorId(string $clientVendorId): self
    {
        $this->clientVendorId = $clientVendorId;
        return $this;
    }

    /**
     * Get the name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the name.
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the address.
     *
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * Set the address.
     *
     * @param string $address
     * @return $this
     */
    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    /**
     * Get the street.
     *
     * @return string|null
     */
    public function getStreet(): ?string
    {
        return $this->street;
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
     * Get the street number.
     *
     * @return string|null
     */
    public function getStreetNumber(): ?string
    {
        return $this->streetNumber;
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
     * Get the building.
     *
     * @return string|null
     */
    public function getBuilding(): ?string
    {
        return $this->building;
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
     * Get the district.
     *
     * @return string|null
     */
    public function getDistrict(): ?string
    {
        return $this->district;
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
     * Get the postal code.
     *
     * @return string|null
     */
    public function getPostalCode(): ?string
    {
        return $this->postalCode;
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
     * Get the rider instructions.
     *
     * @return string|null
     */
    public function getRiderInstructions(): ?string
    {
        return $this->riderInstructions;
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
     * Get the latitude.
     *
     * @return float|null
     */
    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    /**
     * Set the latitude.
     *
     * @param float $latitude
     * @return $this
     */
    public function setLatitude(float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    /**
     * Get the longitude.
     *
     * @return float|null
     */
    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    /**
     * Set the longitude.
     *
     * @param float $longitude
     * @return $this
     */
    public function setLongitude(float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    /**
     * Get the city.
     *
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * Set the city.
     *
     * @param string $city
     * @return $this
     */
    public function setCity(string $city): self
    {
        $this->city = $city;
        return $this;
    }

    /**
     * Get the phone number.
     *
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * Set the phone number.
     *
     * @param string $phoneNumber
     * @return $this
     */
    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    /**
     * Get the currency.
     *
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * Set the currency.
     *
     * @param string $currency
     * @return $this
     */
    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * Get the description.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the description.
     *
     * @param string $description
     * @return $this
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get the locale.
     *
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * Set the locale.
     *
     * @param string $locale
     * @return $this
     */
    public function setLocale(string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Check if the outlet is halal.
     *
     * @return bool|null
     */
    public function isHalal(): ?bool
    {
        return $this->halal;
    }

    /**
     * Set if the outlet is halal.
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
     * Get the users.
     *
     * @return array|null
     */
    public function getUsers(): ?array
    {
        return $this->users;
    }

    /**
     * Set the users.
     *
     * @param array $users
     * @return $this
     */
    public function setUsers(array $users): self
    {
        $this->users = $users;
        return $this;
    }

    /**
     * Add users.
     *
     * @param array $users
     * @return $this
     */
    public function addUsers(array $users): self
    {
        $this->addUser = $users;
        return $this;
    }

    /**
     * Delete users.
     *
     * @param array $users
     * @return $this
     */
    public function deleteUsers(array $users): self
    {
        $this->deleteUser = $users;
        return $this;
    }

    /**
     * Convert the outlet to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [];

        if (null !== $this->name) {
            $data['name'] = $this->name;
        }

        if (null !== $this->address) {
            $data['address'] = $this->address;
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

        if (null !== $this->latitude) {
            $data['latitude'] = $this->latitude;
        }

        if (null !== $this->longitude) {
            $data['longitude'] = $this->longitude;
        }

        if (null !== $this->city) {
            $data['city'] = $this->city;
        }

        if (null !== $this->phoneNumber) {
            $data['phone_number'] = $this->phoneNumber;
        }

        if (null !== $this->currency) {
            $data['currency'] = $this->currency;
        }

        if (null !== $this->description) {
            $data['description'] = $this->description;
        }

        if (null !== $this->locale) {
            $data['locale'] = $this->locale;
        }

        if (null !== $this->halal) {
            $data['halal'] = $this->halal;
        }

        if (null !== $this->addUser) {
            $data['add_user'] = $this->addUser;
        }

        if (null !== $this->deleteUser) {
            $data['delete_user'] = $this->deleteUser;
        }

        return $data;
    }
}
