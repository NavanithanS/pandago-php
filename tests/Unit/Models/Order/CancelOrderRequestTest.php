<?php
namespace Nava\Pandago\Tests\Unit\Models\Order;

use Nava\Pandago\Exceptions\ValidationException;
use Nava\Pandago\Models\Order\CancelOrderRequest;
use Nava\Pandago\Tests\TestCase;

class CancelOrderRequestTest extends TestCase
{
    /**
     * Test that a valid cancellation reason is accepted.
     *
     * @return void
     */
    public function testConstructWithValidReason()
    {
        $request = new CancelOrderRequest('MISTAKE_ERROR');

        $this->assertEquals('MISTAKE_ERROR', $request->getReason());
    }

    /**
     * Test converting the request to an array.
     *
     * @return void
     */
    public function testToArray()
    {
        $request = new CancelOrderRequest('DELIVERY_ETA_TOO_LONG');

        $array = $request->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('reason', $array);
        $this->assertEquals('DELIVERY_ETA_TOO_LONG', $array['reason']);
    }

    /**
     * Test getting valid cancellation reasons.
     *
     * @return void
     */
    public function testGetValidReasons()
    {
        $validReasons = CancelOrderRequest::getValidReasons();

        $this->assertIsArray($validReasons);
        $this->assertContains('MISTAKE_ERROR', $validReasons);
        $this->assertContains('DELIVERY_ETA_TOO_LONG', $validReasons);
        $this->assertContains('REASON_UNKNOWN', $validReasons);
    }

    /**
     * Test that an invalid cancellation reason is rejected.
     *
     * @return void
     */
    public function testConstructWithInvalidReason()
    {
        $this->expectException(ValidationException::class);

        new CancelOrderRequest('INVALID_REASON');
    }

    /**
     * Test that an empty cancellation reason is rejected.
     *
     * @return void
     */
    public function testConstructWithEmptyReason()
    {
        $this->expectException(ValidationException::class);

        new CancelOrderRequest('');
    }

    /**
     * Test that a null cancellation reason is rejected.
     *
     * @return void
     */
    public function testConstructWithNullReason()
    {
        $this->expectException(\TypeError::class);

        new CancelOrderRequest(null);
    }
}
