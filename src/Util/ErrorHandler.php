<?php
namespace Nava\Pandago\Util;

use Nava\Pandago\Exceptions\RequestException;

class ErrorHandler
{
    /**
     * Map of status codes to friendly error messages
     */
    private const ERROR_MAP = [
        // Authentication errors
        401 => [
            'message' => 'Authentication failed. Your authentication token may have expired or is invalid.',
            'action'  => 'Try refreshing the token or check your credentials.',
        ],
        403 => [
            'message' => 'Access forbidden.',
            'action'  => 'Verify that your account has permission to access this resource.',
        ],

        // Client errors
        400 => [
            'message' => 'Invalid request.',
            'action'  => 'Check your request parameters and format.',
        ],
        404 => [
            'message' => 'Resource not found.',
            'action'  => 'Verify the ID or path is correct.',
        ],
        405 => [
            'message' => 'Method not allowed.',
            'action'  => 'Check if the requested operation is supported for this resource.',
        ],
        409 => [
            'message' => 'Conflict with current state of the resource.',
            'action'  => 'The resource may be in a state that doesn\'t allow this operation.',
        ],
        422 => [
            'message' => 'Unprocessable entity.',
            'action'  => 'The request data is likely invalid. Check your parameters.',
        ],
        429 => [
            'message' => 'Too many requests.',
            'action'  => 'You\'ve exceeded the rate limit. Please reduce the frequency of your requests.',
        ],

        // Server errors
        500 => [
            'message' => 'Internal server error.',
            'action'  => 'The pandago API encountered an error. Please try again later or contact support.',
        ],
        502 => [
            'message' => 'Bad gateway.',
            'action'  => 'The pandago API is experiencing issues. Please try again later.',
        ],
        503 => [
            'message' => 'Service unavailable.',
            'action'  => 'The pandago API is temporarily unavailable. Please try again later.',
        ],
        504 => [
            'message' => 'Gateway timeout.',
            'action'  => 'The pandago API request timed out. Please try again later.',
        ],
    ];

    /**
     * Map of error message patterns to specific suggestions
     */
    private const ERROR_PATTERN_MAP = [
        'outlet not found'            => 'Verify that the client vendor ID exists and that you have permission to access it.',
        'no branch found'             => 'Check the sender coordinates. They may be too far from any registered branch.',
        'order is not cancellable'    => 'The order has progressed too far in the delivery process to be cancelled.',
        'order update is not allowed' => 'Order updates may not be allowed in the current country or for the current order status.',
        'access token is expired'     => 'Your authentication token has expired. Request a new token.',
        'invalid credentials'         => 'Your authentication credentials are invalid. Check your client ID, key ID, and private key.',
        'already exists'              => 'A resource with this identifier already exists. Use a different identifier or update the existing resource.',
        'validation failed'           => 'The request data failed validation. Check the format and values of your request parameters.',
    ];

    /**
     * Get a detailed error message with suggestions based on a RequestException
     *
     * @param RequestException $exception
     * @return string
     */
    public static function getDetailedErrorMessage(RequestException $exception): string
    {
        $statusCode = $exception->getCode();
        $message    = $exception->getMessage();
        $data       = $exception->getData();

        $errorDetails = [];

        // Add basic error information
        $errorDetails[] = sprintf("Error %d: %s", $statusCode, $message);

        // Add request context
        if ($exception->getMethod() && $exception->getEndpoint()) {
            $errorDetails[] = sprintf("Request: %s %s", $exception->getMethod(), $exception->getEndpoint());
        }

        // Add status code specific suggestions
        if (isset(self::ERROR_MAP[$statusCode])) {
            $errorInfo      = self::ERROR_MAP[$statusCode];
            $errorDetails[] = sprintf("Suggestion: %s %s", $errorInfo['message'], $errorInfo['action']);
        }

        // Add pattern-specific suggestions
        foreach (self::ERROR_PATTERN_MAP as $pattern => $suggestion) {
            if (stripos($message, $pattern) !== false) {
                $errorDetails[] = sprintf("Tip: %s", $suggestion);
                break; // Only add the first matching suggestion
            }
        }

        // Add API error details if available
        if (! empty($data) && is_array($data)) {
            if (isset($data['errors']) && is_array($data['errors'])) {
                $errorDetails[] = "API reported errors:";
                foreach ($data['errors'] as $error) {
                    if (is_string($error)) {
                        $errorDetails[] = "- $error";
                    } elseif (is_array($error) && isset($error['message'])) {
                        $errorDetails[] = "- {$error['message']}";
                    }
                }
            } elseif (isset($data['error_description'])) {
                $errorDetails[] = "API message: {$data['error_description']}";
            }
        }

        return implode("\n", $errorDetails);
    }

    /**
     * Parse and enhance API error messages from different formats
     *
     * @param array $responseData
     * @param int $statusCode
     * @return string
     */
    public static function parseErrorMessage(array $responseData, int $statusCode): string
    {
        // Try different error message formats based on API responses
        if (isset($responseData['message'])) {
            $message = $responseData['message'];
        } elseif (isset($responseData['error_description'])) {
            $message = $responseData['error_description'];
        } elseif (isset($responseData['error'])) {
            $message = $responseData['error'];
        } elseif (isset($responseData['errors']) && is_array($responseData['errors'])) {
            if (isset($responseData['errors'][0]['message'])) {
                $message = $responseData['errors'][0]['message'];
            } elseif (is_string($responseData['errors'][0])) {
                $message = $responseData['errors'][0];
            } else {
                $message = 'Multiple errors occurred';
            }
        } else {
            $message = 'Unknown error occurred';
        }

        // Add status code context for common HTTP errors
        switch ($statusCode) {
            case 400:
                return "$message (Bad Request)";
            case 401:
                return "$message (Unauthorized)";
            case 403:
                return "$message (Forbidden)";
            case 404:
                return "$message (Not Found)";
            case 409:
                return "$message (Conflict)";
            case 422:
                return "$message (Unprocessable Entity)";
            case 429:
                return "$message (Rate Limit Exceeded)";
            case 500:
                return "$message (Internal Server Error)";
            case 503:
                return "$message (Service Unavailable)";
            default:
                return $message;
        }
    }
}
