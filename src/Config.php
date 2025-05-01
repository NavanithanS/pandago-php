<?php
namespace Nava\Pandago;

use Nava\Pandago\Exceptions\PandagoException;
use Nava\Pandago\Traits\ValidatesParameters;

class Config
{
    use ValidatesParameters;

    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $keyId;

    /**
     * @var string
     */
    protected $scope;

    /**
     * @var string
     */
    protected $privateKey;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var int
     */
    protected $timeout;

    /**
     * @var string
     */
    protected $country;

    /**
     * @var array
     */
    protected $environmentUrls = [
        'sandbox'    => [
            'api'  => 'https://pandago-api-sandbox.deliveryhero.io',
            'auth' => 'https://sts-st.deliveryhero.io',
        ],
        'production' => [
            'api'  => 'https://pandago-api.deliveryhero.io',
            'auth' => 'https://sts.deliveryhero.io',
        ],
    ];

    /**
     * @var array
     */
    protected $supportedCountries = [
        'sg' => 'Singapore',
        'hk' => 'Hong Kong',
        'my' => 'Malaysia',
        'th' => 'Thailand',
        'ph' => 'Philippines',
        'tw' => 'Taiwan',
        'pk' => 'Pakistan',
        'jo' => 'Jordan',
        'fi' => 'Finland',
        'kw' => 'Kuwait',
        'no' => 'Norway',
        'se' => 'Sweden',
    ];

    /**
     * Config constructor.
     *
     * @param string $clientId
     * @param string $keyId
     * @param string $scope
     * @param string $privateKey
     * @param string $country
     * @param string $environment
     * @param int $timeout
     * @throws PandagoException
     */
    public function __construct(
        string $clientId,
        string $keyId,
        string $scope,
        string $privateKey,
        string $country = 'my',
        string $environment = 'sandbox',
        int $timeout = 30
    ) {
        $this->validate([
            'clientId'    => $clientId,
            'keyId'       => $keyId,
            'scope'       => $scope,
            'privateKey'  => $privateKey,
            'country'     => $country,
            'environment' => $environment,
        ], [
            'clientId'    => 'required|string',
            'keyId'       => 'required|string',
            'scope'       => 'required|string',
            'privateKey'  => 'required|string',
            'country'     => 'required|string|in:' . implode(',', array_keys($this->supportedCountries)),
            'environment' => 'required|string|in:sandbox,production',
        ]);

        $this->clientId    = $clientId;
        $this->keyId       = $keyId;
        $this->scope       = $scope;
        $this->privateKey  = $privateKey;
        $this->country     = strtolower($country);
        $this->environment = $environment;
        $this->timeout     = $timeout;
    }

    /**
     * Create a new config instance from an array.
     *
     * @param array $config
     * @return self
     * @throws PandagoException
     */
    public static function fromArray(array $config): self
    {
        return new self(
            $config['client_id'],
            $config['key_id'],
            $config['scope'],
            $config['private_key'],
            $config['country'] ?? 'my',
            $config['environment'] ?? 'sandbox',
            $config['timeout'] ?? 30
        );
    }

    /**
     * Get the client ID.
     *
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * Get the key ID.
     *
     * @return string
     */
    public function getKeyId(): string
    {
        return $this->keyId;
    }

    /**
     * Get the scope.
     *
     * @return string
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * Get the private key.
     *
     * @return string
     */
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    /**
     * Get the country code.
     *
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * Get the environment.
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Get the timeout.
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Get the API base URL.
     *
     * @return string
     */
    public function getApiBaseUrl(): string
    {
        return $this->environmentUrls[$this->environment]['api'] . '/' . $this->country . '/api/v1';
    }

    /**
     * Get the authentication URL.
     *
     * @return string
     */
    public function getAuthUrl(): string
    {
        return $this->environmentUrls[$this->environment]['auth'] . '/oauth2/token';
    }

    /**
     * Check if a country is supported.
     *
     * @param string $country
     * @return bool
     */
    public function isCountrySupported(string $country): bool
    {
        return isset($this->supportedCountries[strtolower($country)]);
    }

    /**
     * Get all supported countries.
     *
     * @return array
     */
    public function getSupportedCountries(): array
    {
        return $this->supportedCountries;
    }
}
