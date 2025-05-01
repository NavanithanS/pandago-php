<?php
namespace Nava\Pandago\Tests\Unit\Models\Order;

use Nava\Pandago\Models\Contact;
use Nava\Pandago\Models\Location;
use Nava\Pandago\Models\Order\Order;
use Nava\Pandago\Models\Timeline;
use Nava\Pandago\Tests\TestCase;

class OrderTest extends TestCase
{
    public function testCreateOrderFromArray()
    {
        $orderData = [
            'order_id'              => 'y0ud-000001',
            'client_order_id'       => 'client-ref-000001',
            'sender'                => [
                'name'         => 'GuangYou',
                'phone_number' => '+601110550716',
                'location'     => [
                    'address'   => '8, Jalan Laguna 1',
                    'latitude'  => 5.3731476,
                    'longitude' => 100.4068053,
                ],
                'notes'        => 'use the left side door',
            ],
            'recipient'             => [
                'name'         => 'Chalit',
                'phone_number' => '+60125918131',
                'location'     => [
                    'address'   => '670, Era Jaya',
                    'latitude'  => 7.3500280,
                    'longitude' => 100.4374034,
                ],
                'notes'        => 'leave at the front door',
            ],
            'distance'              => 906.13,
            'payment_method'        => 'PAID',
            'coldbag_needed'        => false,
            'amount'                => 23.50,
            'status'                => 'NEW',
            'delivery_fee'          => 8.17,
            'timeline'              => [
                'estimated_pickup_time'   => '2025-05-01T01:11:23.123Z',
                'estimated_delivery_time' => '2025-05-01T01:13:37.123Z',
            ],
            'driver'                => [
                'id'           => '12345',
                'name'         => 'John Doe',
                'phone_number' => '+6511111111',
            ],
            'created_at'            => 1536802000,
            'updated_at'            => 1536802000,
            'tracking_link'         => 'https://example.com/test_tracking_path',
            'proof_of_delivery_url' => 'https://example.com/proof_of_delivery/y0ud-000001',
            'proof_of_pickup_url'   => 'https://example.com/proof_of_pickup/y0ud-000001',
            'proof_of_return_url'   => 'https://example.com/proof_of_return/y0ud-000001',
            'delivery_tasks'        => [
                'age_validation_required' => false,
            ],
        ];

        $order = Order::fromArray($orderData);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('y0ud-000001', $order->getOrderId());
        $this->assertEquals('client-ref-000001', $order->getClientOrderId());
        $this->assertEquals('PAID', $order->getPaymentMethod());
        $this->assertEquals(23.50, $order->getAmount());
        $this->assertEquals('NEW', $order->getStatus());
        $this->assertEquals(8.17, $order->getDeliveryFee());
        $this->assertEquals(906.13, $order->getDistance());
        $this->assertEquals(1536802000, $order->getCreatedAt());
        $this->assertEquals(1536802000, $order->getUpdatedAt());
        $this->assertEquals('https://example.com/test_tracking_path', $order->getTrackingLink());
        $this->assertEquals('https://example.com/proof_of_delivery/y0ud-000001', $order->getProofOfDeliveryUrl());
        $this->assertEquals('https://example.com/proof_of_pickup/y0ud-000001', $order->getProofOfPickupUrl());
        $this->assertEquals('https://example.com/proof_of_return/y0ud-000001', $order->getProofOfReturnUrl());

        // Check sender details
        $sender = $order->getSender();
        $this->assertInstanceOf(Contact::class, $sender);
        $this->assertEquals('GuangYou', $sender->getName());
        $this->assertEquals('+601110550716', $sender->getPhoneNumber());
        $this->assertEquals('use the left side door', $sender->getNotes());

        // Check sender location
        $senderLocation = $sender->getLocation();
        $this->assertInstanceOf(Location::class, $senderLocation);
        $this->assertEquals('8, Jalan Laguna 1', $senderLocation->getAddress());
        $this->assertEquals(5.3731476, $senderLocation->getLatitude());
        $this->assertEquals(100.4068053, $senderLocation->getLongitude());

        // Check recipient details
        $recipient = $order->getRecipient();
        $this->assertInstanceOf(Contact::class, $recipient);
        $this->assertEquals('Chalit', $recipient->getName());
        $this->assertEquals('+60125918131', $recipient->getPhoneNumber());
        $this->assertEquals('leave at the front door', $recipient->getNotes());

        // Check recipient location
        $recipientLocation = $recipient->getLocation();
        $this->assertInstanceOf(Location::class, $recipientLocation);
        $this->assertEquals('670, Era Jaya', $recipientLocation->getAddress());
        $this->assertEquals(7.3500280, $recipientLocation->getLatitude());
        $this->assertEquals(100.4374034, $recipientLocation->getLongitude());

        // Check timeline
        $timeline = $order->getTimeline();
        $this->assertInstanceOf(Timeline::class, $timeline);
        $this->assertEquals('2025-05-01T01:11:23.123Z', $timeline->getEstimatedPickupTime());
        $this->assertEquals('2025-05-01T01:13:37.123Z', $timeline->getEstimatedDeliveryTime());

        // Check driver
        $driver = $order->getDriver();
        $this->assertIsArray($driver);
        $this->assertEquals('12345', $driver['id']);
        $this->assertEquals('John Doe', $driver['name']);
        $this->assertEquals('+6511111111', $driver['phone_number']);

        // Check delivery tasks
        $deliveryTasks = $order->getDeliveryTasks();
        $this->assertIsArray($deliveryTasks);
        $this->assertFalse($deliveryTasks['age_validation_required']);
    }

    public function testFillOrder()
    {
        $order = new Order();

        $order->fill([
            'order_id'        => 'y0ud-000002',
            'client_order_id' => 'client-ref-000002',
            'payment_method'  => 'CASH_ON_DELIVERY',
            'amount'          => 45.00,
            'description'     => 'Test order',
        ]);

        $this->assertEquals('y0ud-000002', $order->getOrderId());
        $this->assertEquals('client-ref-000002', $order->getClientOrderId());
        $this->assertEquals('CASH_ON_DELIVERY', $order->getPaymentMethod());
        $this->assertEquals(45.00, $order->getAmount());
        $this->assertEquals('Test order', $order->getDescription());
    }

    public function testSettersAndGetters()
    {
        $order = new Order();

        // Test setters
        $location  = new Location('Test Address', 1.234, 5.678);
        $recipient = new Contact('Test Name', '+6599999999', $location);

        $order->setClientOrderId('test-client-order');
        $order->setRecipient($recipient);
        $order->setPaymentMethod('PAID');
        $order->setColdbagNeeded(true);
        $order->setAmount(99.99);
        $order->setCollectFromCustomer(50.0);
        $order->setDescription('Test description');
        $order->setDeliveryTasks(['age_validation_required' => true]);
        $order->setPreorderedFor(1622505600);

        // Test getters
        $this->assertEquals('test-client-order', $order->getClientOrderId());
        $this->assertSame($recipient, $order->getRecipient());
        $this->assertEquals('PAID', $order->getPaymentMethod());
        $this->assertTrue($order->isColdbagNeeded());
        $this->assertEquals(99.99, $order->getAmount());
        $this->assertEquals(50.0, $order->getCollectFromCustomer());
        $this->assertEquals('Test description', $order->getDescription());
        $this->assertEquals(['age_validation_required' => true], $order->getDeliveryTasks());
        $this->assertEquals(1622505600, $order->getPreorderedFor());
    }

    public function testToArray()
    {
        $order = new Order();

        $location  = new Location('Test Address', 1.234, 5.678);
        $recipient = new Contact('Test Name', '+6599999999', $location);

        $order->setClientOrderId('test-client-order');
        $order->setRecipient($recipient);
        $order->setPaymentMethod('PAID');
        $order->setAmount(99.99);
        $order->setDescription('Test description');

        $array = $order->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('test-client-order', $array['client_order_id']);
        $this->assertEquals('PAID', $array['payment_method']);
        $this->assertEquals(99.99, $array['amount']);
        $this->assertEquals('Test description', $array['description']);
        $this->assertArrayHasKey('recipient', $array);
    }
}
