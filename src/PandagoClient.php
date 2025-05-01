<?php
namespace Nava\Pandago;

use GuzzleHttp\Client as HttpClient;
use Nava\Pandago\Auth\TokenManager;
use Nava\Pandago\Contracts\ClientInterface;
use Nava\Pandago\Contracts\HttpClientInterface;
use Nava\Pandago\Exceptions\PandagoException;
use Nava\Pandago\Exceptions\RequestException;
use Nava\Pandago\Http\GuzzleHttpClient;
use Nava\Pandago\Resources\OrderResource;
use Nava\Pandago\Resources\OutletResource;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PandagoClient implements ClientInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var HttpClientInterface
     */
    protected $httpClient;

    /**
     * @var TokenManager
     */
    protected $tokenManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var OrderResource
     */
    private $orderResource;

    /**
     * @var OutletResource
     */
    private $outletResource;

    /**
     * PandagoClient constructor.
     *
     * @param Config $config
     * @param HttpClientInterface|null $httpClient
     * @param LoggerInterface|null $logger
     */
    public function __construct(Config $config, HttpClientInterface $httpClient = null, LoggerInterface $logger = null)
    {
        $this->config     = $config;
        $this->httpClient = $httpClient ?: new GuzzleHttpClient(new HttpClient([
            'base_uri'    => $this->config->getApiBaseUrl(),
            'timeout'     => $this->config->getTimeout(),
            'http_errors' => false,
        ]));
        $this->logger       = $logger ?: new NullLogger();
        $this->tokenManager = new TokenManager($this->config, $this->httpClient, $this->logger);
    }

    /**
     * Get the order resource.
     *
     * @return OrderResource
     */
    public function orders(): OrderResource
    {
        if (! $this->orderResource) {
            $this->orderResource = new OrderResource($this);
        }

        return $this->orderResource;
    }

    /**
     * Get the outlet resource.
     *
     * @return OutletResource
     */
    public function outlets(): OutletResource
    {
        if (! $this->outletResource) {
            $this->outletResource = new OutletResource($this);
        }

        return $this->outletResource;
    }

    /**
     * Make a request to the API.
     *
     * @param string $method
     * @param string $path
     * @param array $options
     * @return array
     * @throws PandagoException
     * @throws RequestException
     */
    public function request(string $method, string $path, array $options = []): array
    {
        $token            = $this->tokenManager->getToken();
        $sanitizedOptions = $options;

        $options['headers'] = array_merge(
            $options['headers'] ?? [],
            [
                'Authorization' => 'Bearer ' . $token->getAccessToken(),
                'Accept'        => 'application/json',
            ]
        );

        // Sanitize options for logging (remove sensitive data)
        $sanitizedOptions = $this->sanitizeOptions($options);

        $this->logger->debug('Making API request', [
            'method'  => $method,
            'path'    => $path,
            'options' => $sanitizedOptions,
        ]);

        try {
            $response   = $this->httpClient->request($method, $path, $options);
            $statusCode = $response->getStatusCode();
            $contents   = $response->getBody()->getContents();

            $this->logger->debug('API response received', [
                'status_code' => $statusCode,
                'contents'    => $contents,
            ]);

            if ($statusCode >= 400) {
                $data = json_decode($contents, true) ?: ['message' => 'Unknown error'];

                // Extract the error message with fallbacks for different API response formats
                $errorMessage = $data['message'] ?? $data['error_description'] ??
                    ($data['errors'][0]['message'] ?? 'Unknown error');

                // Add context about the environment if it's a server error
                if ($statusCode >= 500) {
                    $contextInfo = sprintf(
                        'Environment: %s, Country: %s',
                        $this->config->getEnvironment(),
                        $this->config->getCountry()
                    );
                    $errorMessage .= ' (' . $contextInfo . ')';
                }

                throw new RequestException(
                    $errorMessage,
                    $statusCode,
                    null,
                    $data,
                    $method,
                    $path,
                    $sanitizedOptions
                );
            }

            // Handle empty response for 204 No Content
            if (204 === $statusCode) {
                return [];
            }

            $data = json_decode($contents, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new PandagoException(
                    'Invalid JSON response from API: ' . json_last_error_msg()
                );
            }

            return $data;
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $errorContext = [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'method'    => $method,
                'path'      => $path,
            ];

            $this->logger->error('API request failed', $errorContext);

            // Provide more context in the error message
            $errorMessage = sprintf(
                'API request failed: %s %s - %s',
                $method,
                $path,
                $e->getMessage()
            );

            throw new RequestException(
                $errorMessage,
                $e->getCode() ?: 0,
                $e,
                [],
                $method,
                $path,
                $sanitizedOptions
            );
        }
    }

    /**
     * Sanitize request options for logging.
     *
     * @param array $options
     * @return array
     */
    private function sanitizeOptions(array $options): array
    {
        $sanitized = $options;

        // Redact sensitive data
        if (isset($sanitized['headers']['Authorization'])) {
            $sanitized['headers']['Authorization'] = 'Bearer [redacted]';
        }

        return $sanitized;
    }

    /**
     * Get the configuration.
     *
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }
}
