<?php
namespace Nava\Pandago\Auth;

use Firebase\JWT\JWT;
use Nava\Pandago\Config;
use Nava\Pandago\Contracts\HttpClientInterface;
use Nava\Pandago\Exceptions\AuthenticationException;
use Nava\Pandago\Models\Auth\Token;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class TokenManager
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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Token|null
     */
    protected $token;

    /**
     * TokenManager constructor.
     *
     * @param Config $config
     * @param HttpClientInterface $httpClient
     * @param LoggerInterface $logger
     */
    public function __construct(Config $config, HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->config     = $config;
        $this->httpClient = $httpClient;
        $this->logger     = $logger;
    }

    /**
     * Get a valid token.
     *
     * @return Token
     * @throws AuthenticationException
     */
    public function getToken(): Token
    {
        if ($this->token && ! $this->token->isExpired()) {
            return $this->token;
        }

        try {
            $this->token = $this->requestToken();
            return $this->token;
        } catch (\Exception $e) {
            $this->logger->error('Failed to request token', [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
            ]);

            throw new AuthenticationException('Failed to authenticate with pandago: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Request a new token from the pandago API.
     *
     * @return Token
     * @throws AuthenticationException
     */
    protected function requestToken(): Token
    {
        $assertion = $this->generateAssertion();

        $response = $this->httpClient->request('POST', $this->config->getAuthUrl(), [
            'headers'     => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'form_params' => [
                'grant_type'            => 'client_credentials',
                'client_id'             => $this->config->getClientId(),
                'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
                'client_assertion'      => $assertion,
                'scope'                 => $this->config->getScope(),
            ],
        ]);

        $statusCode = $response->getStatusCode();
        $contents   = $response->getBody()->getContents();

        if (200 !== $statusCode) {
            $error = json_decode($contents, true);
            throw new AuthenticationException(
                'Failed to authenticate with pandago: ' . ($error['error_description'] ?? 'Unknown error')
            );
        }

        $data = json_decode($contents, true);

        if (! isset($data['access_token'], $data['expires_in'])) {
            throw new AuthenticationException('Invalid token response from pandago');
        }

        return new Token($data['access_token'], (int) $data['expires_in']);
    }

    /**
     * Generate a JWT assertion.
     *
     * @return string
     */
    protected function generateAssertion(): string
    {
        $now = time();
        $exp = $now + 3600; // Token valid for 1 hour

        $payload = [
            'iss' => $this->config->getClientId(),
            'sub' => $this->config->getClientId(),
            'jti' => Uuid::uuid4()->toString(),
            'exp' => $exp,
            'aud' => $this->getAudience(),
        ];

        $privateKey = $this->config->getPrivateKey();

        return JWT::encode($payload, $privateKey, 'RS256', null, [
            'kid' => $this->config->getKeyId(),
        ]);
    }

    /**
     * Get the audience for the JWT.
     *
     * @return string
     */
    protected function getAudience(): string
    {
        return $this->config->getEnvironment() === 'sandbox'
        ? 'https://sts-st.deliveryhero.io'
        : 'https://sts.deliveryhero.io';
    }
}
