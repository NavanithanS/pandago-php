<?php
namespace Nava\Pandago\Models\Order;

use Nava\Pandago\Exceptions\ValidationException;
use Nava\Pandago\Models\Contact;
use Nava\Pandago\Traits\ValidatesParameters;

class CreateOrderRequest
{
    use ValidatesParameters;

    /**
     * @var string|null
     */
    protected $clientOrderId;

    /**
     * @var Contact|null
     */
    protected $sender;

    /**
     * @var string|null
     */
    protected $clientVendorId;

    /**
     * @var Contact
     */
    protected $recipient;

    /**
     * @var string
     */
    protected $paymentMethod;

    /**
     * @var bool
     */
    protected $coldbagNeeded;

    /**
     * @var float
     */
    protected $amount;

    /**
     * @var float|null
     */
    protected $collectFromCustomer;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var array
     */
    protected $deliveryTasks;

    /**
     * @var int|null
     */
    protected $preorderedFor;

    /**
     * CreateOrderRequest constructor.
     *
     * @param Contact $recipient
     * @param float $amount
     * @param string $description
     * @param string $paymentMethod
     * @param bool $coldbagNeeded
     * @throws ValidationException
     */
    public function __construct(
        Contact $recipient,
        float $amount,
        string $description,
        string $paymentMethod = 'PAID',
        bool $coldbagNeeded = false
    ) {
        $this->validate([
            'amount'        => $amount,
            'description'   => $description,
            'paymentMethod' => $paymentMethod,
        ], [
            'amount'        => 'required|numeric|min:0',
            'description'   => 'required|string|max:200',
            'paymentMethod' => 'required|string|in:PAID,CASH_ON_DELIVERY',
        ]);

        $this->recipient     = $recipient;
        $this->amount        = $amount;
        $this->description   = $description;
        $this->paymentMethod = $paymentMethod;
        $this->coldbagNeeded = $coldbagNeeded;
        $this->deliveryTasks = [
            'age_validation_required' => false,
        ];
    }

    /**
     * Create a new CreateOrderRequest instance from an array.
     *
     * @param array $data
     * @return self
     * @throws ValidationException
     */
    public static function fromArray(array $data): self
    {
        $instance = new self(
            Contact::fromArray($data['recipient']),
            $data['amount'],
            $data['description'],
            $data['payment_method'] ?? 'PAID',
            $data['coldbag_needed'] ?? false
        );

        if (isset($data['client_order_id'])) {
            $instance->setClientOrderId($data['client_order_id']);
        }

        if (isset($data['sender'])) {
            if (isset($data['sender']['client_vendor_id'])) {
                $instance->setClientVendorId($data['sender']['client_vendor_id']);
            } else {
                $instance->setSender(Contact::fromArray($data['sender']));
            }
        }

        if (isset($data['collect_from_customer'])) {
            $instance->setCollectFromCustomer($data['collect_from_customer']);
        }

        if (isset($data['delivery_tasks'])) {
            $instance->setDeliveryTasks($data['delivery_tasks']);
        }

        if (isset($data['preordered_for'])) {
            $instance->setPreorderedFor($data['preordered_for']);
        }

        return $instance;
    }

    /**
     * Set the client order ID.
     *
     * @param string $clientOrderId
     * @return $this
     */
    public function setClientOrderId(string $clientOrderId): self
    {
        $this->clientOrderId = $clientOrderId;
        return $this;
    }

    /**
     * Set the sender.
     *
     * @param Contact $sender
     * @return $this
     */
    public function setSender(Contact $sender): self
    {
        $this->sender         = $sender;
        $this->clientVendorId = null;
        return $this;
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
        $this->sender         = null;
        return $this;
    }

    /**
     * Set the collect from customer amount.
     *
     * @param float $amount
     * @return $this
     * @throws ValidationException
     */
    public function setCollectFromCustomer(float $amount): self
    {
        $this->validate([
            'collectFromCustomer' => $amount,
        ], [
            'collectFromCustomer' => 'numeric|min:0',
        ]);

        $this->collectFromCustomer = $amount;
        return $this;
    }

    /**
     * Set the delivery tasks.
     *
     * @param array $deliveryTasks
     * @return $this
     */
    public function setDeliveryTasks(array $deliveryTasks): self
    {
        $this->deliveryTasks = $deliveryTasks;
        return $this;
    }

    /**
     * Set age validation requirement.
     *
     * @param bool $required
     * @return $this
     */
    public function setAgeValidationRequired(bool $required): self
    {
        $this->deliveryTasks['age_validation_required'] = $required;
        return $this;
    }

    /**
     * Set the preordered for timestamp.
     *
     * @param int $timestamp
     * @return $this
     */
    public function setPreorderedFor(int $timestamp): self
    {
        $this->preorderedFor = $timestamp;
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
            'recipient'      => $this->recipient->toArray(),
            'amount'         => $this->amount,
            'payment_method' => $this->paymentMethod,
            'coldbag_needed' => $this->coldbagNeeded,
            'description'    => $this->description,
            'delivery_tasks' => $this->deliveryTasks,
        ];

        if (null !== $this->clientOrderId) {
            $data['client_order_id'] = $this->clientOrderId;
        }

        if (null !== $this->sender) {
            $data['sender'] = $this->sender->toArray();
        } elseif (null !== $this->clientVendorId) {
            $data['sender'] = [
                'client_vendor_id' => $this->clientVendorId,
            ];
        }

        if (null !== $this->collectFromCustomer) {
            $data['collect_from_customer'] = $this->collectFromCustomer;
        }

        if (null !== $this->preorderedFor) {
            $data['preordered_for'] = $this->preorderedFor;
        }

        return $data;
    }
}
