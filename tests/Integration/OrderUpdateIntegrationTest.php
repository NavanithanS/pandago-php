<?php
namespace Nava\Pandago\Tests\Integration;

/**
 * Test Cases for Order Updates
 *
 * PUT /orders/{order_id} endpoint testing
 */
class OrderUpdateIntegrationTest extends TestCase
{
    /**
     * Test Case 11.1.1: Update order payment method (Happy Path)
     */
    public function testUpdateOrderPaymentMethod()
    {
        // TODO:Create order with CASH_ON_DELIVERY
        // TODO:Update to PAID with amount = 0
        // TODO:Verify update successful
    }

    /**
     * Test Case 11.1.2: Update order location (Happy Path)
     */
    public function testUpdateOrderLocation()
    {
        // TODO:Create order
        // TODO:Update recipient location
        // TODO:Verify coordinates updated
    }

    /**
     * Test Case 11.2.1: Update picked up order (Unhappy Path)
     */
    public function testUpdatePickedUpOrder()
    {
        // TODO:Try to update order that's already picked up
        // TODO:Should return 422 error
    }

    /**
     * Test Case 11.2.2: Invalid payment method change (Unhappy Path)
     */
    public function testInvalidPaymentMethodChange()
    {
        // TODO:Try to change PAID to CASH_ON_DELIVERY (not supported)
        // TODO:Should return 400 error
    }
}
