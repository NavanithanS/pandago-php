<?php
namespace Nava\Pandago\Models\Order;

use Nava\Pandago\Exceptions\ValidationException;
use Nava\Pandago\Models\Contact;
use Nava\Pandago\Traits\ValidatesParameters;

class CreateOrderRequest
{
    use ValidatesParameters;

    /**
     * @var Contact
     */
    protected $recipient;

    /**
     * @var float
     */
    protected $amount;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var Contact|string|null
     */
    protected $sender;

    /**
     * @var string|null
     */
    protected $clientOrderId;

    /**
     * @var bool
     */
    protected $coldbagNeeded = false;

    /**
     * @var string
     */
    protected $paymentMethod = 'PAID';

    /**
     * @var float|null
     */
    protected $collectFromCustomer;

    /**
     * @var int|null
     */
    protected $preorderedFor;

    /**
     * @var array
     */
    protected $deliveryTasks = ['age_validation_required' => false];

    /**
     * Constructor.
     *
     * @param Contact $recipient Recipient contact information
     * @param float $amount Amount
     * @param string $description Description
     * @throws ValidationException If the parameters are invalid
     */
    public function __construct(Contact $recipient, float $amount, string $description)
    {
        $this->validate([
            'recipient'   => [$recipient, 'required'],
            'amount'      => [$amount, 'required', 'numeric', 'min:0'],
            'description' => [$description, 'required', 'max:200'],
        ]);

        $this->recipient   = $recipient;
        $this->amount      = $amount;
        $this->description = $description;
    }

    /**
     * Create a new instance from an array.
     *
     * @param array $data Request data
     * @return static
     * @throws ValidationException If the data is invalid
     */
    public static function fromArray(array $data)
    {
        $recipient = isset($data['recipient'])
        ? Contact::fromArray($data['recipient'])
        : null;

        $instance = new static(
            $recipient,
            $data['amount'] ?? 0,
            $data['description'] ?? ''
        );

        if (isset($data['sender'])) {
            if (isset($data['sender']['client_vendor_id'])) {
                $instance->setClientVendorId($data['sender']['client_vendor_id']);
            } else {
                $instance->setSender(Contact::fromArray($data['sender']));
            }
        }

        if (isset($data['client_order_id'])) {
            $instance->setClientOrderId($data['client_order_id']);
        }

        if (isset($data['payment_method'])) {
            $instance->setPaymentMethod($data['payment_method']);
        }

        if (isset($data['coldbag_needed'])) {
            $instance->setColdbagNeeded($data['coldbag_needed']);
        }

        if (isset($data['collect_from_customer'])) {
            $instance->setCollectFromCustomer($data['collect_from_customer']);
        }

        if (isset($data['preordered_for'])) {
            $instance->setPreorderedFor($data['preordered_for']);
        }

        if (isset($data['delivery_tasks'])) {
            $instance->setDeliveryTasks($data['delivery_tasks']);
        }

        return $instance;
    }

    /**
     * Set the client order ID.
     *
     * @param string $clientOrderId Client order ID
     * @return $this
     */
    public function setClientOrderId(string $clientOrderId)
    {
        $this->clientOrderId = $clientOrderId;

        return $this;
    }

    /**
     * Get the client order ID.
     *
     * @return string|null
     */
    public function getClientOrderId()
    {
        return $this->clientOrderId;
    }

    /**
     * Set the sender.
     *
     * @param Contact $sender Sender contact information
     * @return $this
     */
    public function setSender(Contact $sender)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * Set the client vendor ID.
     *
     * @param string $clientVendorId Client vendor ID
     * @return $this
     */
    public function setClientVendorId(string $clientVendorId)
    {
        $this->sender = $clientVendorId;

        return $this;
    }

    /**
     * Get the sender.
     *
     * @return Contact|string|null
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * Set whether a cold bag is needed.
     *
     * @param bool $coldbagNeeded Whether a cold bag is needed
     * @return $this
     */
    public function setColdbagNeeded(bool $coldbagNeeded)
    {
        $this->coldbagNeeded = $coldbagNeeded;

        return $this;
    }

    /**
     * Get whether a cold bag is needed.
     *
     * @return bool
     */
    public function isColdbagNeeded()
    {
        return $this->coldbagNeeded;
    }

    /**
     * Set the payment method.
     *
     * @param string $paymentMethod Payment method (PAID or CASH_ON_DELIVERY)
     * @return $this
     * @throws ValidationException If the payment method is invalid
     */
    public function setPaymentMethod(string $paymentMethod)
    {
        $validMethods = ['PAID', 'CASH_ON_DELIVERY'];

        if (! in_array($paymentMethod, $validMethods)) {
            throw new ValidationException("Payment method must be one of: " . implode(', ', $validMethods));
        }

        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * Get the payment method.
     *
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * Set the amount to collect from customer.
     *
     * @param float $collectFromCustomer Amount to collect from customer
     * @return $this
     * @throws ValidationException If the amount is invalid
     */
    public function setCollectFromCustomer(float $collectFromCustomer)
    {
        $this->validate([
            'collectFromCustomer' => [$collectFromCustomer, 'numeric', 'min:0'],
        ]);

        $this->collectFromCustomer = $collectFromCustomer;

        return $this;
    }

    /**
     * Get the amount to collect from customer.
     *
     * @return float|null
     */
    public function getCollectFromCustomer()
    {
        return $this->collectFromCustomer;
    }

    /**
     * Set the preordered for timestamp.
     *
     * @param int $preorderedFor Unix timestamp
     * @return $this
     */
    public function setPreorderedFor(int $preorderedFor)
    {
        $this->preorderedFor = $preorderedFor;

        return $this;
    }

    /**
     * Get the preordered for timestamp.
     *
     * @return int|null
     */
    public function getPreorderedFor()
    {
        return $this->preorderedFor;
    }

    /**
     * Set the delivery tasks.
     *
     * @param array $deliveryTasks Delivery tasks
     * @return $this
     */
    public function setDeliveryTasks(array $deliveryTasks)
    {
        $this->deliveryTasks = $deliveryTasks;

        return $this;
    }

    /**
     * Get the delivery tasks.
     *
     * @return array
     */
    public function getDeliveryTasks()
    {
        return $this->deliveryTasks;
    }

    /**
     * Get the recipient.
     *
     * @return Contact
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * Get the amount.
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Get the description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray()
    {
        $data = [
            'recipient'      => $this->recipient->toArray(),
            'amount'         => $this->amount,
            'payment_method' => $this->paymentMethod,
            'coldbag_needed' => $this->coldbagNeeded,
            'description'    => $this->description,
            'delivery_tasks' => $this->deliveryTasks,
        ];

        if ($this->sender) {
            if (is_string($this->sender)) {
                $data['sender'] = ['client_vendor_id' => $this->sender];
            } else {
                $data['sender'] = $this->sender->toArray();
            }
        }

        if ($this->clientOrderId) {
            $data['client_order_id'] = $this->clientOrderId;
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
