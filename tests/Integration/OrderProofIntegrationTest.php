<?php
namespace Nava\Pandago\Tests\Integration;

/**
 * Test Cases for Order Proof Retrieval
 *
 * GET /orders/proof_of_* endpoints
 */
class OrderProofIntegrationTest extends TestCase
{
    /**
     * Test Case 12.1.1: Get proof of delivery (Happy Path)
     */
    public function testGetProofOfDelivery()
    {
        // TODO:Create and deliver order
        // TODO:Get proof of delivery
        // TODO:Verify base64 image data
    }

    /**
     * Test Case 12.1.2: Get proof of pickup (Happy Path)
     */
    public function testGetProofOfPickup()
    {
        // TODO:Create and pickup order
        // TODO:Get proof of pickup
        // TODO:Verify base64 image data
    }

    /**
     * Test Case 12.2.1: Get proof for non-delivered order (Unhappy Path)
     */
    public function testGetProofForNonDeliveredOrder()
    {
        // TODO:Try to get proof for NEW order
        // TODO:Should return 404
    }
}
