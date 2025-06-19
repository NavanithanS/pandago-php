<?php
namespace Nava\Pandago\Integration;

use Illuminate\Http\Request;
use Nava\Pandago\Client;
use Nava\Pandago\Config;
use Nava\Pandago\Exceptions\RequestException;
use Nava\Pandago\Models\Order\CancelOrderRequest;
use Nava\Pandago\Models\Order\CreateOrderRequest;
use Nava\Pandago\Models\Order\Order;
use Nava\Pandago\PandagoAddress;
use Nava\Pandago\PandagoClient;
use Carbon\Carbon;
use Log;

class OrderCreate 
{
    /**
     * @var \Nava\Pandago\PandagoClient
     */
    protected $client;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string|null
     */
    protected $orderId;

    /**
     * Time threshold in minutes for preorder vs immediate order
     * If delivery is more than this many minutes away, use preorder
     */
    protected $preorderThresholdMinutes;

    /**
     * Business hours configuration
     */
    protected $businessHours;

    public function __construct()
    {
        $clientId = env('PANDAGO_CLIENT_ID');
        $keyId = env('PANDAGO_KEY_ID');
        $scope = env('PANDAGO_SCOPE');
        $privateKey =  file_get_contents(storage_path(env('PANDAGO_PRIVATE_KEY')));
        $country = env('PANDAGO_COUNTRY');
        $environment = env('PANDAGO_ENVIRONMENT');
        $timeout = env('PANDAGO_TIMEOUT');

        $this->config = new Config($clientId, $keyId, $scope, $privateKey, $country, $environment, $timeout);
        $this->client = new PandagoClient($this->config);
        
        // Configuration for preorder threshold (default: 45 minutes)
        $this->preorderThresholdMinutes = env('CHECKOUT_DELIVERY_MIN_TIME', 45);
        
        // Default business hours (24-hour format)
        $this->businessHours = [
            1 => ['open' => 9, 'close' => 22],  // Monday
            2 => ['open' => 9, 'close' => 22],  // Tuesday
            3 => ['open' => 9, 'close' => 22],  // Wednesday
            4 => ['open' => 9, 'close' => 22],  // Thursday
            5 => ['open' => 9, 'close' => 23],  // Friday
            6 => ['open' => 10, 'close' => 23], // Saturday
            7 => ['open' => 10, 'close' => 21], // Sunday
        ];
    }

    /**
     * Smart order creation that automatically determines immediate vs preorder
     * 
     * @param mixed $request
     * @param mixed $store
     * @param string|int|null $deliveryDateTime Optional delivery time override
     * @return array
     */
    public function createOrder($request, $store, $deliveryDateTime = null)
    {
        // Determine delivery time
        $scheduledTime = $this->determineDeliveryTime($request, $deliveryDateTime);
        
        // Log the decision process
        $now = time();
        $timeUntilDelivery = $scheduledTime - $now;
        $timeUntilDeliveryMinutes = round($timeUntilDelivery / 60);
        
        Log::info("Pandago Order Decision:", [
            'current_time' => date('Y-m-d H:i:s', $now),
            'delivery_time' => date('Y-m-d H:i:s', $scheduledTime),
            'minutes_until_delivery' => $timeUntilDeliveryMinutes,
            'threshold_minutes' => $this->preorderThresholdMinutes,
            'will_use_preorder' => $timeUntilDeliveryMinutes > $this->preorderThresholdMinutes
        ]);

        // Decide between immediate and preorder
        if ($timeUntilDeliveryMinutes > $this->preorderThresholdMinutes) {
            return $this->createPreorderInternal($request, $store, $scheduledTime);
        } else {
            return $this->createImmediateOrderInternal($request, $store);
        }
    }

    /**
     * Determine the delivery time based on various inputs
     * 
     * @param mixed $request
     * @param string|int|null $deliveryDateTime
     * @return int Unix timestamp
     */
    protected function determineDeliveryTime($request, $deliveryDateTime = null)
    {
        // If explicit delivery time provided, use it
        if ($deliveryDateTime !== null) {
            if (is_numeric($deliveryDateTime)) {
                return (int)$deliveryDateTime;
            }
            return strtotime($deliveryDateTime);
        }

        // Check if there's delivery info in the request
        $scheduledTime = null;
        
        // Check various possible sources of delivery time
        if (is_object($request)) {
            // From ShopOrder object
            if (isset($request->content['shipping_date_time'])) {
                $scheduledTime = strtotime($request->content['shipping_date_time']);
            } elseif (isset($request->content['delivery_time'])) {
                $scheduledTime = strtotime($request->content['delivery_time']);
            } elseif (isset($request->content['scheduled_delivery'])) {
                $scheduledTime = strtotime($request->content['scheduled_delivery']);
            }
            // Check for preorder items that might have delivery dates
            elseif (isset($request->items)) {
                $latestDeliveryDate = null;
                foreach ($request->items as $item) {
                    if (isset($item->model) && method_exists($item->model, 'preorder_deliver_date')) {
                        $itemDeliveryDate = $item->model->preorder_deliver_date;
                        if ($itemDeliveryDate && (!$latestDeliveryDate || $itemDeliveryDate > $latestDeliveryDate)) {
                            $latestDeliveryDate = $itemDeliveryDate;
                        }
                    }
                }
                if ($latestDeliveryDate) {
                    $scheduledTime = $latestDeliveryDate->timestamp;
                }
            }
        } elseif (is_array($request)) {
            // From array format
            if (isset($request['shipping_date_time'])) {
                $scheduledTime = strtotime($request['shipping_date_time']);
            } elseif (isset($request['delivery_time'])) {
                $scheduledTime = strtotime($request['delivery_time']);
            }
        }

        // If no scheduled time found, default to immediate (current time)
        if (!$scheduledTime || $scheduledTime <= time()) {
            $scheduledTime = time();
        }

        return $scheduledTime;
    }

    /**
     * Create an immediate order
     * 
     * @param mixed $request
     * @param mixed $store
     * @return array
     */
    protected function createImmediateOrderInternal($request, $store)
    {
        Log::info("Creating immediate Pandago order");
        
        $data = PandagoAddress::prepareCreateOrderData($request);
        
        // Create recipient
        $recipient = PandagoAddress::getCustomerContact($data);

        // Create order request with a client order ID for tracing
        $clientOrderId = $data['refno'];

        $orderRequest = new CreateOrderRequest(
            $recipient,
            $data['subtotal'],
            'Immediate Order',
        );

        $orderRequest->setClientOrderId($clientOrderId);

        //set sender 
        $sender = PandagoAddress::getOutletContact($store);
        $orderRequest->setSender($sender);
        $orderRequest->setPaymentMethod('PAID');
        $orderRequest->setColdbagNeeded(true);

        // DO NOT set preorderedFor for immediate orders

        return $this->executeOrderCreation($orderRequest, 'immediate');
    }

    /**
     * Create a preorder with scheduled delivery time
     * 
     * @param mixed $request
     * @param mixed $store
     * @param int $scheduledTime Unix timestamp
     * @return array
     */
    protected function createPreorderInternal($request, $store, $scheduledTime)
    {
        // Validate the scheduled time
        $this->validatePreorderTime($scheduledTime);
        
        Log::info("Creating Pandago preorder for: " . date('Y-m-d H:i:s', $scheduledTime));
        
        $data = PandagoAddress::prepareCreateOrderData($request);
        
        // Create recipient
        $recipient = PandagoAddress::getCustomerContact($data);

        // Create order request with a client order ID for tracing
        $clientOrderId = $data['refno'];

        $orderRequest = new CreateOrderRequest(
            $recipient,
            $data['subtotal'],
            'Scheduled Order for ' . date('M j, Y g:i A', $scheduledTime),
        );

        $orderRequest->setClientOrderId($clientOrderId);

        //set sender 
        $sender = PandagoAddress::getOutletContact($store);
        $orderRequest->setSender($sender);
        $orderRequest->setPaymentMethod('PAID');
        $orderRequest->setColdbagNeeded(true);

        // CRITICAL: Set the preorder time
        $orderRequest->setPreorderedFor($scheduledTime);

        return $this->executeOrderCreation($orderRequest, 'preorder', $scheduledTime);
    }

    /**
     * Execute the actual order creation
     * 
     * @param CreateOrderRequest $orderRequest
     * @param string $orderType
     * @param int|null $scheduledTime
     * @return array
     */
    protected function executeOrderCreation(CreateOrderRequest $orderRequest, $orderType, $scheduledTime = null)
    {
        //for print to check the data
        $requestOrderPayload = $orderRequest->toArray();

        // Log the payload to verify preordered_for is included for preorders
        Log::info("Pandago Order Payload ({$orderType}):", $requestOrderPayload);

        try {
            $order = $this->client->orders()->create($orderRequest);

            // Store order ID for cleanup
            $this->orderId = $order->getOrderId();

            //Display additional order details
            $orderArray = $order->toArray();
            $orderArray['order_id'] = $this->orderId;
            $orderArray['order_type'] = $orderType;
            
            if ($scheduledTime) {
                $orderArray['scheduled_for'] = date('Y-m-d H:i:s', $scheduledTime);
                $orderArray['scheduled_timestamp'] = $scheduledTime;
            }

            // Verify recipient details
            $orderRecipient = $order->getRecipient();
            $recipientLocation = $orderRecipient->getLocation();

            Log::info("Pandago order created successfully:", [
                'order_id' => $this->orderId,
                'type' => $orderType,
                'scheduled_for' => $scheduledTime ? date('Y-m-d H:i:s', $scheduledTime) : 'immediate'
            ]);

            return $orderArray;

        } catch (RequestException $e) {
            // Handle common integration test failures
            if ($e->getCode() === 404 && strpos($e->getMessage(), 'Outlet not found') !== false) {
                Log::error('Pandago order failed: Outlet not found. Please configure a valid client vendor ID.');
            } elseif ($e->getCode() === 422 && strpos($e->getMessage(), 'No Branch found') !== false) {
                Log::error('Pandago order failed: No branch found that is close enough to the given sender coordinates.');
            } else {
                Log::error('Pandago order failed:', [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'order_type' => $orderType
                ]);
            }
            throw $e;
        }
    }

    /**
     * Validate preorder time against business rules
     * 
     * @param int $scheduledTime
     * @throws \InvalidArgumentException
     */
    protected function validatePreorderTime($scheduledTime)
    {
        $now = time();
        
        // Must be in the future
        if ($scheduledTime <= $now) {
            throw new \InvalidArgumentException('Delivery time must be in the future');
        }
        
        // Check if delivery time is too far in the future (e.g., max 7 days)
        $maxFutureTime = $now + (7 * 24 * 60 * 60); // 7 days
        if ($scheduledTime > $maxFutureTime) {
            throw new \InvalidArgumentException('Delivery time cannot be more than 7 days in the future');
        }
        
        // Check business hours
        $deliveryHour = (int)date('H', $scheduledTime);
        $deliveryDay = (int)date('N', $scheduledTime); // 1 = Monday, 7 = Sunday
        
        if (isset($this->businessHours[$deliveryDay])) {
            $dayHours = $this->businessHours[$deliveryDay];
            if ($deliveryHour < $dayHours['open'] || $deliveryHour >= $dayHours['close']) {
                throw new \InvalidArgumentException('Delivery time is outside business hours');
            }
        }
    }

    /**
     * Force create an immediate order (bypass time checking)
     * 
     * @param mixed $request
     * @param mixed $store
     * @return array
     */
    public function createImmediateOrder($request, $store)
    {
        return $this->createImmediateOrderInternal($request, $store);
    }

    /**
     * Force create a preorder (with explicit time)
     * 
     * @param mixed $request
     * @param mixed $store
     * @param string|int $deliveryDateTime
     * @return array
     */
    public function createPreorder($request, $store, $deliveryDateTime)
    {
        // Convert string datetime to Unix timestamp
        $scheduledDeliveryTime = is_numeric($deliveryDateTime) ? 
            (int)$deliveryDateTime : 
            strtotime($deliveryDateTime);

        // Validate the timestamp
        if ($scheduledDeliveryTime === false || $scheduledDeliveryTime <= time()) {
            throw new \InvalidArgumentException('Invalid delivery time. Must be a future timestamp or valid datetime string.');
        }

        return $this->createPreorderInternal($request, $store, $scheduledDeliveryTime);
    }

    /**
     * Set custom preorder threshold
     * 
     * @param int $minutes
     * @return self
     */
    public function setPreorderThreshold($minutes)
    {
        $this->preorderThresholdMinutes = $minutes;
        return $this;
    }

    /**
     * Set custom business hours
     * 
     * @param array $businessHours
     * @return self
     */
    public function setBusinessHours(array $businessHours)
    {
        $this->businessHours = $businessHours;
        return $this;
    }

    /**
     * Get current configuration
     * 
     * @return array
     */
    public function getConfiguration()
    {
        return [
            'preorder_threshold_minutes' => $this->preorderThresholdMinutes,
            'business_hours' => $this->businessHours
        ];
    }
}
