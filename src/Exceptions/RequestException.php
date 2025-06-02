<?php
namespace Nava\Pandago\Exceptions;

use Throwable;

class RequestException extends PandagoException
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var string|null
     */
    protected $method;

    /**
     * @var string|null
     */
    protected $endpoint;

    /**
     * @var array
     */
    protected $requestOptions;

    protected $rawMessage;

    /**
     * RequestException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param array $data
     * @param string|null $method
     * @param string|null $endpoint
     * @param array $requestOptions
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        Throwable $previous = null,
        array $data = [],
        string $method = null,
        string $endpoint = null,
        array $requestOptions = []
    ) {
        $this->rawMessage = $message;

        // Enhance the error message with more context if method and endpoint are provided
        if ($method && $endpoint) {
            $message = sprintf(
                "[%s %s] %s",
                $method,
                $endpoint,
                $message
            );
        }

        parent::__construct($message, $code, $previous);

        $this->data           = $data;
        $this->method         = $method;
        $this->endpoint       = $endpoint;
        $this->requestOptions = $requestOptions;
    }

    /**
     * Get the raw error message without formatting
     *
     * @return string
     */
    public function getRawMessage(): string
    {
        return $this->rawMessage;
    }

    /**
     * Get the error data.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get the HTTP method used in the request.
     *
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * Get the API endpoint that was called.
     *
     * @return string|null
     */
    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    /**
     * Get the request options.
     *
     * @return array
     */
    public function getRequestOptions(): array
    {
        return $this->requestOptions;
    }

    /**
     * Get a human-friendly description of the error.
     *
     * @return string
     */
    public function getFriendlyMessage(): string
    {
        $message = $this->getMessage();

        // Add suggestions based on error code
        switch ($this->getCode()) {
            case 401:
                return "$message\nSuggestion: Your authentication token may have expired or is invalid. Try refreshing the token.";

            case 404:
                return "$message\nSuggestion: The requested resource was not found. Check if the ID or path is correct.";

            case 403:
                return "$message\nSuggestion: You don't have permission to access this resource. Check your credentials and scopes.";

            case 422:
                return "$message\nSuggestion: The request data is invalid. Check your request parameters and format.";

            case 409:
                if (strpos($message, 'Order is not cancellable') !== false) {
                    return "$message\nSuggestion: The order may have progressed too far in the delivery process to be cancelled.";
                }
                return "$message\nSuggestion: There's a conflict with the current state of the resource.";

            case 429:
                return "$message\nSuggestion: You've exceeded the rate limit. Please reduce the frequency of your requests.";

            case 500:
            case 502:
            case 503:
            case 504:
                return "$message\nSuggestion: The pandago API is experiencing issues. Please try again later or contact support.";

            default:
                return $message;
        }
    }
}
