<?php
namespace Nava\Pandago\Models;

use Nava\Pandago\Exceptions\ValidationException;
use Nava\Pandago\Traits\ValidatesParameters;

class Contact
{
    use ValidatesParameters;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $phoneNumber;

    /**
     * @var Location
     */
    protected $location;

    /**
     * @var string|null
     */
    protected $notes;

    /**
     * Contact constructor.
     *
     * @param string $name
     * @param string $phoneNumber
     * @param Location $location
     * @param string|null $notes
     * @throws ValidationException
     */
    public function __construct(string $name, string $phoneNumber, Location $location, ?string $notes = null)
    {
        $this->validate([
            'name'        => $name,
            'phoneNumber' => $phoneNumber,
            'notes'       => $notes,
        ], [
            'name'        => 'required|string|max:255',
            'phoneNumber' => 'required|string',
            'notes'       => 'string|max:2048',
        ]);

        $this->name        = $name;
        $this->phoneNumber = $phoneNumber;
        $this->location    = $location;
        $this->notes       = $notes;
    }

    /**
     * Create a new Contact instance from an array.
     *
     * @param array $data
     * @return self
     * @throws ValidationException
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            $data['phone_number'],
            Location::fromArray($data['location']),
            $data['notes'] ?? null
        );
    }

    /**
     * Get the name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the phone number.
     *
     * @return string
     */
    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    /**
     * Get the location.
     *
     * @return Location
     */
    public function getLocation(): Location
    {
        return $this->location;
    }

    /**
     * Get the notes.
     *
     * @return string|null
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * Convert the contact to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'name'         => $this->name,
            'phone_number' => $this->phoneNumber,
            'location'     => $this->location->toArray(),
        ];

        if (null !== $this->notes) {
            $data['notes'] = $this->notes;
        }

        return $data;
    }
}
