<?php
namespace Nava\Pandago\Models\Order;

use Nava\Pandago\Models\Contact;
use Nava\Pandago\Models\Timeline;
use Nava\Pandago\Traits\ValidatesParameters;

class Order
{
    use ValidatesParameters;

    /**
     * @var string|null
     */
    protected $orderId;

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
     * @var float|null
     */
    protected $distance;

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
     * @var string|null
     */
    protected $status;

    /**
     * @var float|null
     */
    protected $deliveryFee;

    /**
     * @var Timeline|null
     */
    protected $timeline;

    /**
     * @var array|null
     */
    protected $driver;

    /**
     * @var int|null
     */
    protected $createdAt;

    /**
     * @var int|null
     */
    protected $updatedAt;

    /**
     * @var string|null
     */
    protected $trackingLink;

    /**
     * @var string|null
     */
    protected $proofOfDeliveryUrl;

    /**
     * @var string|null
     */
    protected $proofOfPickupUrl;

    /**
     * @var string|null
     */
    protected $proofOfReturnUrl;

    /**
     * @var array|null
     */
    protected $cancellation;

    /**
     * @var array
     */
    protected $deliveryTasks;

    /**
     * @var int|null
     */
    protected $preorderedFor;

    /**
     * Create a new Order instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Create a new Order instance from an array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Fill the order with attributes.
     *
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes): self
    {
        if (isset($attributes['order_id'])) {
            $this->orderId = $attributes['order_id'];
        }

        if (isset($attributes['client_order_id'])) {
            $this->clientOrderId = $attributes['client_order_id'];
        }

        if (isset($attributes['sender'])) {
            $this->sender = $attributes['sender'] instanceof Contact
            ? $attributes['sender']
            : Contact::fromArray($attributes['sender']);
        }

        if (isset($attributes['client_vendor_id'])) {
            $this->clientVendorId = $attributes['client_vendor_id'];
        }

        if (isset($attributes['recipient'])) {
            $this->recipient = $attributes['recipient'] instanceof Contact
            ? $attributes['recipient']
            : Contact::fromArray($attributes['recipient']);
        }

        if (isset($attributes['distance'])) {
            $this->distance = (float) $attributes['distance'];
        }

        if (isset($attributes['payment_method'])) {
            $this->paymentMethod = $attributes['payment_method'];
        }

        if (isset($attributes['coldbag_needed'])) {
            $this->coldbagNeeded = (bool) $attributes['coldbag_needed'];
        }

        if (isset($attributes['amount'])) {
            $this->amount = (float) $attributes['amount'];
        }

        if (isset($attributes['collect_from_customer'])) {
            $this->collectFromCustomer = (float) $attributes['collect_from_customer'];
        }

        if (isset($attributes['description'])) {
            $this->description = $attributes['description'];
        }

        if (isset($attributes['status'])) {
            $this->status = $attributes['status'];
        }

        if (isset($attributes['delivery_fee'])) {
            $this->deliveryFee = (float) $attributes['delivery_fee'];
        }

        if (isset($attributes['timeline'])) {
            $this->timeline = $attributes['timeline'] instanceof Timeline
            ? $attributes['timeline']
            : Timeline::fromArray($attributes['timeline']);
        }

        if (isset($attributes['driver'])) {
            $this->driver = $attributes['driver'];
        }

        if (isset($attributes['created_at'])) {
            $this->createdAt = (int) $attributes['created_at'];
        }

        if (isset($attributes['updated_at'])) {
            $this->updatedAt = (int) $attributes['updated_at'];
        }

        if (isset($attributes['tracking_link'])) {
            $this->trackingLink = $attributes['tracking_link'];
        }

        if (isset($attributes['proof_of_delivery_url'])) {
            $this->proofOfDeliveryUrl = $attributes['proof_of_delivery_url'];
        }

        if (isset($attributes['proof_of_pickup_url'])) {
            $this->proofOfPickupUrl = $attributes['proof_of_pickup_url'];
        }

        if (isset($attributes['proof_of_return_url'])) {
            $this->proofOfReturnUrl = $attributes['proof_of_return_url'];
        }

        if (isset($attributes['cancellation'])) {
            $this->cancellation = $attributes['cancellation'];
        }

        if (isset($attributes['delivery_tasks'])) {
            $this->deliveryTasks = $attributes['delivery_tasks'];
        } else {
            $this->deliveryTasks = [
                'age_validation_required' => false,
            ];
        }

        if (isset($attributes['preordered_for'])) {
            $this->preorderedFor = (int) $attributes['preordered_for'];
        }

        return $this;
    }

    /**
     * Get the order ID.
     *
     * @return string|null
     */
    public function getOrderId(): ?string
    {
        return $this->orderId;
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
     * Get the sender.
     *
     * @return Contact|null
     */
    public function getSender(): ?Contact
    {
        return $this->sender;
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
     * Get the recipient.
     *
     * @return Contact|null
     */
    public function getRecipient(): ?Contact
    {
        return $this->recipient;
    }

    /**
     * Set the recipient.
     *
     * @param Contact $recipient
     * @return $this
     */
    public function setRecipient(Contact $recipient): self
    {
        $this->recipient = $recipient;
        return $this;
    }

    /**
     * Get the distance.
     *
     * @return float|null
     */
    public function getDistance(): ?float
    {
        return $this->distance;
    }

    /**
     * Get the payment method.
     *
     * @return string|null
     */
    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
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
     * Check if a coldbag is needed.
     *
     * @return bool|null
     */
    public function isColdbagNeeded(): ?bool
    {
        return $this->coldbagNeeded;
    }

    /**
     * Set if a coldbag is needed.
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
     * Get the amount.
     *
     * @return float|null
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * Set the amount.
     *
     * @param float $amount
     * @return $this
     */
    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Get the amount to collect from customer.
     *
     * @return float|null
     */
    public function getCollectFromCustomer(): ?float
    {
        return $this->collectFromCustomer;
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
     * Get the status.
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Get the delivery fee.
     *
     * @return float|null
     */
    public function getDeliveryFee(): ?float
    {
        return $this->deliveryFee;
    }

    /**
     * Get the timeline.
     *
     * @return Timeline|null
     */
    public function getTimeline(): ?Timeline
    {
        return $this->timeline;
    }

    /**
     * Get the driver.
     *
     * @return array|null
     */
    public function getDriver(): ?array
    {
        return $this->driver;
    }

    /**
     * Get the created at timestamp.
     *
     * @return int|null
     */
    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }

    /**
     * Get the updated at timestamp.
     *
     * @return int|null
     */
    public function getUpdatedAt(): ?int
    {
        return $this->updatedAt;
    }

    /**
     * Get the tracking link.
     *
     * @return string|null
     */
    public function getTrackingLink(): ?string
    {
        return $this->trackingLink;
    }

    /**
     * Get the proof of delivery URL.
     *
     * @return string|null
     */
    public function getProofOfDeliveryUrl(): ?string
    {
        return $this->proofOfDeliveryUrl;
    }

    /**
     * Get the proof of pickup URL.
     *
     * @return string|null
     */
    public function getProofOfPickupUrl(): ?string
    {
        return $this->proofOfPickupUrl;
    }

    /**
     * Get the proof of return URL.
     *
     * @return string|null
     */
    public function getProofOfReturnUrl(): ?string
    {
        return $this->proofOfReturnUrl;
    }

    /**
     * Get the cancellation.
     *
     * @return array|null
     */
    public function getCancellation(): ?array
    {
        return $this->cancellation;
    }

    /**
     * Get the delivery tasks.
     *
     * @return array|null
     */
    public function getDeliveryTasks(): ?array
    {
        return $this->deliveryTasks;
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
     * Get the preordered for timestamp.
     *
     * @return int|null
     */
    public function getPreorderedFor(): ?int
    {
        return $this->preorderedFor;
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
     * Convert the order to an array for API requests.
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [];

        // Only include properties that should be sent to the API
        if (null !== $this->clientOrderId) {
            $data['client_order_id'] = $this->clientOrderId;
        }

        if (null !== $this->orderId) {
            $data['order_id'] = $this->orderId;
        }

        // Handle sender information
        if (null !== $this->sender) {
            $data['sender'] = $this->sender->toArray();

            // Add client_vendor_id to the sender array if it exists
            if (null !== $this->clientVendorId) {
                $data['sender']['client_vendor_id'] = $this->clientVendorId;
            }
        } elseif (null !== $this->clientVendorId) {
            // Only use client_vendor_id alone if no sender object exists
            $data['sender'] = ['client_vendor_id' => $this->clientVendorId];
        }

        if (null !== $this->recipient) {
            $data['recipient'] = $this->recipient->toArray();
        }

        if (null !== $this->paymentMethod) {
            $data['payment_method'] = $this->paymentMethod;
        }

        if (null !== $this->coldbagNeeded) {
            $data['coldbag_needed'] = $this->coldbagNeeded;
        }

        if (null !== $this->amount) {
            $data['amount'] = $this->amount;
        }

        if (null !== $this->collectFromCustomer) {
            $data['collect_from_customer'] = $this->collectFromCustomer;
        }

        if (null !== $this->description) {
            $data['description'] = $this->description;
        }

        if (null !== $this->deliveryTasks) {
            $data['delivery_tasks'] = $this->deliveryTasks;
        }

        if (null !== $this->preorderedFor) {
            $data['preordered_for'] = $this->preorderedFor;
        }

        if (null !== $this->trackingLink) {
            $data['trackingLink'] = $this->trackingLink;
        }

        return $data;
    }
}
