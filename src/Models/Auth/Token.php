<?php
namespace Nava\Pandago\Models\Auth;

class Token
{
    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @var int
     */
    protected $expiresAt;

    /**
     * Token constructor.
     *
     * @param string $accessToken
     * @param int $expiresIn
     */
    public function __construct(string $accessToken, int $expiresIn)
    {
        $this->accessToken = $accessToken;
        $this->expiresAt   = time() + $expiresIn;
    }

    /**
     * Get the access token.
     *
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * Get the expiration time.
     *
     * @return int
     */
    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    /**
     * Check if the token is expired.
     *
     * @param int $threshold
     * @return bool
     */
    public function isExpired(int $threshold = 60): bool
    {
        return $this->expiresAt - $threshold < time();
    }
}
