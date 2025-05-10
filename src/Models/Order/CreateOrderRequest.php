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
    private $recipient;

    /**
     * @var float
     */
    private $amount;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string|null
     */
    private $clientOrderId;

    /**
     * @var Contact|null
     */
    private $sender;

    /**
     * @var string|null
     */
    private $clientVendorId;

    /**
     * @var string
     */
    private $paymentMethod = 'PAID';

    /**
     * @var bool
     */
    private $coldbagNeeded = false;

    /**
     * @var array
     */
    private $deliveryTasks = [];

    /**
     * @var int|null
     */
    private $preorderedFor;

    /**
     * @var float|null
     */
    private $collectFromCustomer;

    /**
     * Create a new order request.
     *
     * @param Contact $recipient
     * @param float $amount
     * @param string $description
     * @throws ValidationException
     */
    public function __construct(Contact $recipient, float $amount, string $description)
    {
        $this->recipient   = $recipient;
        $this->amount      = $amount;
        $this->description = $description;

        // Define the validation rules for the constructor parameters
        $rules = [
            'recipient'   => 'required',
            'amount'      => 'required|numeric|min:0',
            'description' => 'required|string|max:200',
        ];

        // Pass both parameters and rules to the validate method
        $this->validate([
            'recipient'   => $this->recipient,
            'amount'      => $this->amount,
            'description' => $this->description,
        ], $rules);
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
        $this->sender = $sender;
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
        return $this;
    }

    /**
     * Set the payment method.
     *
     * @param string $paymentMethod
     * @return $this
     */
    public function setPaymentMethod(string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    /**
     * Set the coldbag needed flag.
     *
     * @param bool $coldbagNeeded
     * @return $this
     */
    public function setColdbagNeeded(bool $coldbagNeeded): self
    {
        $this->coldbagNeeded = $coldbagNeeded;
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
     * Set the preordered for timestamp.
     *
     * @param int $preorderedFor
     * @return $this
     */
    public function setPreorderedFor(int $preorderedFor): self
    {
        $this->preorderedFor = $preorderedFor;
        return $this;
    }

    /**
     * Set the amount to collect from customer.
     *
     * @param float $collectFromCustomer
     * @return $this
     */
    public function setCollectFromCustomer(float $collectFromCustomer): self
    {
        $this->collectFromCustomer = $collectFromCustomer;
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
            'description'    => $this->description,
            'payment_method' => $this->paymentMethod,
            'coldbag_needed' => $this->coldbagNeeded,
        ];

        if (null !== $this->clientOrderId) {
            $data['client_order_id'] = $this->clientOrderId;
        }

        if (null !== $this->sender) {
            $data['sender'] = $this->sender->toArray();
        }

        if (null !== $this->clientVendorId) {
            $data['sender'] = ['client_vendor_id' => $this->clientVendorId];
        }

        if (! empty($this->deliveryTasks)) {
            $data['delivery_tasks'] = $this->deliveryTasks;
        }

        if (null !== $this->preorderedFor) {
            $data['preordered_for'] = $this->preorderedFor;
        }

        if (null !== $this->collectFromCustomer) {
            $data['collect_from_customer'] = $this->collectFromCustomer;
        }

        return $data;
    }
}
