<?php
namespace Nava\Pandago\Tests\Unit\Util;

use Nava\Pandago\Exceptions\RequestException;
use Nava\Pandago\Tests\TestCase;
use Nava\Pandago\Util\ErrorHandler;

class ErrorHandlerTest extends TestCase
{
    /**
     * Test parsing error messages from different response formats.
     *
     * @return void
     */
    public function testParseErrorMessage()
    {
        // Test with 'message' format
        $data    = ['message' => 'Something went wrong'];
        $message = ErrorHandler::parseErrorMessage($data, 400);
        $this->assertEquals('Something went wrong (Bad Request)', $message);

        // Test with 'error_description' format
        $data    = ['error_description' => 'Authentication failed'];
        $message = ErrorHandler::parseErrorMessage($data, 401);
        $this->assertEquals('Authentication failed (Unauthorized)', $message);

        // Test with 'error' format
        $data    = ['error' => 'Rate limit exceeded'];
        $message = ErrorHandler::parseErrorMessage($data, 429);
        $this->assertEquals('Rate limit exceeded (Rate Limit Exceeded)', $message);

        // Test with 'errors' array format
        $data    = ['errors' => [['message' => 'Invalid parameters']]];
        $message = ErrorHandler::parseErrorMessage($data, 422);
        $this->assertEquals('Invalid parameters (Unprocessable Entity)', $message);

        // Test with 'errors' string array format
        $data    = ['errors' => ['Invalid JSON format']];
        $message = ErrorHandler::parseErrorMessage($data, 400);
        $this->assertEquals('Invalid JSON format (Bad Request)', $message);

        // Test with unknown format
        $data    = ['unknown_key' => 'Unknown error'];
        $message = ErrorHandler::parseErrorMessage($data, 500);
        $this->assertEquals('Unknown error occurred (Internal Server Error)', $message);
    }

    /**
     * Test getting detailed error messages for RequestExceptions.
     *
     * @return void
     */
    public function testGetDetailedErrorMessage()
    {
        // Create a RequestException with various properties
        $exception = new RequestException(
            'Order not found',
            404,
            null,
            ['message' => 'Order not found'],
            'GET',
            '/orders/invalid-id',
            ['headers' => ['Accept' => 'application/json']]
        );

        $detailedMessage = ErrorHandler::getDetailedErrorMessage($exception);

        // Ensure the message contains key components
        $this->assertStringContainsString('Error 404: Order not found', $detailedMessage);
        $this->assertStringContainsString('Request: GET /orders/invalid-id', $detailedMessage);
        $this->assertStringContainsString('Suggestion:', $detailedMessage);
        $this->assertStringContainsString('Verify the ID or path is correct', $detailedMessage);

        // Test with pattern-specific error
        $exception = new RequestException(
            'Order is not cancellable',
            409,
            null,
            ['message' => 'Order is not cancellable'],
            'DELETE',
            '/orders/123',
            []
        );

        $detailedMessage = ErrorHandler::getDetailedErrorMessage($exception);
        $this->assertStringContainsString('The order has progressed too far', $detailedMessage);
    }

    /**
     * Test friendly message suggestions for different status codes.
     *
     * @return void
     */
    public function testRequestExceptionFriendlyMessage()
    {
        // Test 401 error
        $exception = new RequestException('Unauthorized', 401);
        $this->assertStringContainsString('authentication token may have expired', $exception->getFriendlyMessage());

        // Test 404 error
        $exception = new RequestException('Not found', 404);
        $this->assertStringContainsString('Check if the ID or path is correct', $exception->getFriendlyMessage());

        // Test 422 error
        $exception = new RequestException('Invalid data', 422);
        $this->assertStringContainsString('Check your request parameters', $exception->getFriendlyMessage());

        // Test 500 error
        $exception = new RequestException('Server error', 500);
        $this->assertStringContainsString('Please try again later', $exception->getFriendlyMessage());

        // Test specific error pattern
        $exception = new RequestException('Order is not cancellable', 409);
        $this->assertStringContainsString('may have progressed too far', $exception->getFriendlyMessage());
    }
}
