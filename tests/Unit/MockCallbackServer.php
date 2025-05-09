<?php
namespace Nava\Pandago\Tests\Util;

/**
 * A simple mock server to handle Pandago callbacks for testing purposes.
 *
 * This class provides a way to simulate receiving and processing callbacks
 * from Pandago during testing. It can be used to verify callback handling
 * without requiring an actual public endpoint.
 *
 * Usage:
 * 1. Start the mock server before creating an order
 * 2. Create an order that will trigger callbacks
 * 3. After some time, check received callbacks
 * 4. Stop the mock server
 */
class MockCallbackServer
{
    /**
     * @var array
     */
    private $receivedCallbacks = [];

    /**
     * @var string
     */
    private $callbackPath;

    /**
     * @var resource|null
     */
    private $socket;

    /**
     * @var int
     */
    private $port;

    /**
     * @var bool
     */
    private $isRunning = false;

    /**
     * @var callable|null
     */
    private $callbackHandler;

    /**
     * Constructor.
     *
     * @param int $port The port to listen on (default: 8000)
     * @param string $callbackPath The path for the callback URL (default: /pandago-callback)
     * @param callable|null $callbackHandler A custom callback handler function
     */
    public function __construct(int $port = 8000, string $callbackPath = '/pandago-callback', callable $callbackHandler = null)
    {
        $this->port            = $port;
        $this->callbackPath    = $callbackPath;
        $this->callbackHandler = $callbackHandler;
    }

    /**
     * Start the mock server.
     *
     * @return bool True if the server was started successfully
     */
    public function start(): bool
    {
        if ($this->isRunning) {
            return true;
        }

        // Create the server socket
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (false === $this->socket) {
            echo "Failed to create socket: " . socket_strerror(socket_last_error()) . "\n";
            return false;
        }

        // Set socket options
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);

        // Bind to the port
        if (socket_bind($this->socket, '127.0.0.1', $this->port) === false) {
            echo "Failed to bind socket: " . socket_strerror(socket_last_error($this->socket)) . "\n";
            socket_close($this->socket);
            return false;
        }

        // Start listening
        if (socket_listen($this->socket, 5) === false) {
            echo "Failed to listen on socket: " . socket_strerror(socket_last_error($this->socket)) . "\n";
            socket_close($this->socket);
            return false;
        }

        // Set socket to non-blocking mode
        socket_set_nonblock($this->socket);

        $this->isRunning = true;
        echo "Mock callback server started on http://localhost:{$this->port}{$this->callbackPath}\n";

        return true;
    }

    /**
     * Stop the mock server.
     *
     * @return void
     */
    public function stop(): void
    {
        if ($this->socket && $this->isRunning) {
            socket_close($this->socket);
            $this->socket    = null;
            $this->isRunning = false;
            echo "Mock callback server stopped\n";
        }
    }

    /**
     * Process incoming connections and callbacks.
     *
     * This method should be called periodically to check for and process
     * incoming callback requests.
     *
     * @param int $timeout Maximum time to wait for connections (microseconds)
     * @return void
     */
    public function processCallbacks(int $timeout = 100000): void
    {
        if (! $this->isRunning || ! $this->socket) {
            return;
        }

        // Check for an incoming connection
        $client = @socket_accept($this->socket);
        if ($client) {
            // Process the connection
            $this->handleConnection($client);
            socket_close($client);
        }

        // Wait a bit before checking again
        usleep($timeout);
    }

    /**
     * Handle an incoming connection.
     *
     * @param resource $client The client socket
     * @return void
     */
    private function handleConnection($client): void
    {
        // Read the request
        $request = '';
        while ($buffer = socket_read($client, 2048, PHP_NORMAL_READ)) {
            $request .= $buffer;

            // End of HTTP request
            if (strpos($request, "\r\n\r\n") !== false) {
                break;
            }
        }

        // Parse the request
        $requestLines = explode("\r\n", $request);
        $requestLine  = explode(' ', $requestLines[0]);

        if (count($requestLine) < 3) {
            $this->sendResponse($client, 400, 'Bad Request');
            return;
        }

        $method = $requestLine[0];
        $path   = $requestLine[1];

        // Only process POST requests to the callback path
        if ('POST' === $method && $path === $this->callbackPath) {
            // Extract content length
            $contentLength = 0;
            foreach ($requestLines as $line) {
                if (strpos($line, 'Content-Length:') === 0) {
                    $contentLength = (int) trim(substr($line, 16));
                    break;
                }
            }

            // Read the body if there's content
            $body = '';
            if ($contentLength > 0) {
                // Read the body content
                $body = socket_read($client, $contentLength);
            }

            // Process the callback
            $this->processCallback($body);

            // Send success response
            $this->sendResponse($client, 200, 'OK', '{"success":true}');
        } else {
            // Not found or method not allowed
            $this->sendResponse($client, 404, 'Not Found');
        }
    }

    /**
     * Process a callback payload.
     *
     * @param string $body The request body
     * @return void
     */
    private function processCallback(string $body): void
    {
        // Parse the JSON body
        $payload = json_decode($body, true);

        if (! $payload) {
            echo "Received invalid JSON callback\n";
            return;
        }

        // Store the callback
        $this->receivedCallbacks[] = $payload;

        // Log the callback
        echo "Received callback: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n";

        // If a custom handler is provided, call it
        if ($this->callbackHandler) {
            call_user_func($this->callbackHandler, $payload);
        }
    }

    /**
     * Send an HTTP response.
     *
     * @param resource $client The client socket
     * @param int $statusCode The HTTP status code
     * @param string $statusText The HTTP status text
     * @param string $body Optional response body
     * @return void
     */
    private function sendResponse($client, int $statusCode, string $statusText, string $body = ''): void
    {
        $response = "HTTP/1.1 {$statusCode} {$statusText}\r\n";
        $response .= "Content-Type: application/json\r\n";
        $response .= "Content-Length: " . strlen($body) . "\r\n";
        $response .= "Connection: close\r\n";
        $response .= "\r\n";
        $response .= $body;

        socket_write($client, $response, strlen($response));
    }

    /**
     * Get all received callbacks.
     *
     * @return array
     */
    public function getReceivedCallbacks(): array
    {
        return $this->receivedCallbacks;
    }

    /**
     * Get callbacks for a specific order.
     *
     * @param string $orderId The order ID to filter by
     * @return array
     */
    public function getCallbacksForOrder(string $orderId): array
    {
        return array_filter($this->receivedCallbacks, function ($callback) use ($orderId) {
            return isset($callback['order_id']) && $callback['order_id'] === $orderId;
        });
    }

    /**
     * Get callback URL for this mock server.
     *
     * @return string
     */
    public function getCallbackUrl(): string
    {
        return "http://localhost:{$this->port}{$this->callbackPath}";
    }
}
