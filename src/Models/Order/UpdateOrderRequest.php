<?php
namespace Nava\Pandago\Models\Order;

use Nava\Pandago\Exceptions\ValidationException;
use Nava\Pandago\Models\Location;
use Nava\Pandago\Traits\ValidatesParameters;

class UpdateOrderRequest
{
    use ValidatesParameters;

    /**
     * @var string|null
     */
    protected $paymentMethod;

    /**
     * @var float|null
     */
    protected $amount;

    /**
     * @var Location|null
     */
    protected $location;

    /**
     * @var string|null
     */
    protected $locationNotes;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * UpdateOrderRequest constructor.
     */
    public function __construct()
    {
        //
    }

    /**
     * Create a new UpdateOrderRequest instance from an array.
     *
     * @param array $data
     * @return self
     * @throws ValidationException
     */
    public static function fromArray(array $data): self
    {
        $instance = new self();

        if (isset($data['payment_method'])) {
            $instance->setPaymentMethod($data['payment_method']);
        }

        if (isset($data['amount'])) {
            $instance->setAmount($data['amount']);
        }

        if (isset($data['location'])) {
            $location = new Location(
                $data['location']['address'] ?? '',
                $data['location']['latitude'],
                $data['location']['longitude'],
                $data['location']['postalcode'] ?? null
            );
            $instance->setLocation($location, $data['location']['notes'] ?? null);
        }

        if (isset($data['description'])) {
            $instance->setDescription($data['description']);
        }

        return $instance;
    }

    /**
     * Set the payment method.
     *
     * @param string $paymentMethod
     * @return $this
     * @throws ValidationException
     */
    public function setPaymentMethod(string $paymentMethod): self
    {
        $this->validate([
            'paymentMethod' => $paymentMethod,
        ], [
            'paymentMethod' => 'required|string|in:PAID,CASH_ON_DELIVERY',
        ]);

        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    /**
     * Set the amount.
     *
     * @param float $amount
     * @return $this
     * @throws ValidationException
     */
    public function setAmount(float $amount): self
    {
        $this->validate([
            'amount' => $amount,
        ], [
            'amount' => 'required|numeric|min:0',
        ]);

        $this->amount = $amount;
        return $this;
    }

    /**
     * Set the location.
     *
     * @param Location $location
     * @param string|null $notes
     * @return $this
     */
    public function setLocation(Location $location, ?string $notes = null): self
    {
        $this->location      = $location;
        $this->locationNotes = $notes;
        return $this;
    }

    /**
     * Set the description.
     *
     * @param string $description
     * @return $this
     * @throws ValidationException
     */
    public function setDescription(string $description): self
    {
        $this->validate([
            'description' => $description,
        ], [
            'description' => 'required|string|max:200',
        ]);

        $this->description = $description;
        return $this;
    }

    /**
     * Convert the request to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [];

        if (null !== $this->paymentMethod) {
            $data['payment_method'] = $this->paymentMethod;
        }

        if (null !== $this->amount) {
            $data['amount'] = $this->amount;
        }

        if (null !== $this->location) {
            $locationData = $this->location->toArray();

            if (null !== $this->locationNotes) {
                $locationData['notes'] = $this->locationNotes;
            }

            $data['location'] = $locationData;
        }

        if (null !== $this->description) {
            $data['description'] = $this->description;
        }

        return $data;
    }
}
