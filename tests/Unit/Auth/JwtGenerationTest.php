<?php
namespace Nava\Pandago\Tests\Unit\Auth;

use Firebase\JWT\JWT;
use Mockery;
use Nava\Pandago\Auth\TokenManager;
use Nava\Pandago\Config;
use Nava\Pandago\Contracts\HttpClientInterface;
use Nava\Pandago\Models\Auth\Token;
use Nava\Pandago\Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\NullLogger;
use ReflectionClass;

/**
 * Test cases for JWT token generation in the Pandago client.
 *
 * These tests verify:
 * 1. JWT token generation is correctly implemented
 * 2. The expiration timestamp is set properly in the future
 * 3. The JWT token is used for authentication with the API
 */
class JwtGenerationTest extends TestCase
{
    /**
     * Test Case 1: Generate JWT token.
     *
     * Verifies that a properly structured JWT token is generated with all required fields.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testGenerateJwtToken()
    {
        // Skip if private key is not available
        if (! $this->checkRequiredConfig(['private_key'])) {
            $this->markTestSkipped('Private key is required for this test');
        }

        echo "\n\n✅ TEST CASE 1: Generate JWT token\n";

        // Get test config
        $config = Config::fromArray($this->getConfig());

        // Create HTTP client mock
        $httpClient = Mockery::mock(HttpClientInterface::class);

        // Create token manager
        $tokenManager = new TokenManager($config, $httpClient, new NullLogger());

        // Use reflection to access protected method
        $reflection = new ReflectionClass(TokenManager::class);
        $method     = $reflection->getMethod('generateAssertion');
        $method->setAccessible(true);

        // Generate JWT token
        $jwt = $method->invoke($tokenManager);
        echo "✓ Successfully generated JWT token: " . substr($jwt, 0, 20) . "...\n";

        // Verify token structure
        $parts = explode('.', $jwt);
        $this->assertCount(3, $parts, 'JWT should have three parts: header, payload, signature');
        echo "✓ Verified JWT structure (header.payload.signature)\n";

        // Decode header and payload
        $header  = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[0])), true);
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);

        // Verify header
        $this->assertEquals('RS256', $header['alg']);
        $this->assertEquals('JWT', $header['typ']);
        $this->assertEquals($config->getKeyId(), $header['kid']);
        echo "✓ Verified JWT header with algorithm RS256 and proper key ID\n";

        // Verify payload
        $this->assertEquals($config->getClientId(), $payload['iss']);
        $this->assertEquals($config->getClientId(), $payload['sub']);
        $this->assertNotEmpty($payload['jti'], 'JWT ID should not be empty');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $payload['jti'], 'JWT ID should be a valid UUID');

        // Verify audience based on environment
        $expectedAudience = $config->getEnvironment() === 'sandbox'
        ? 'https://sts.deliveryhero.io'
        : 'https://sts.deliveryhero.io';
        $this->assertEquals($expectedAudience, $payload['aud']);
        echo "✓ Verified JWT payload with proper issuer, subject, and audience\n";
    }

    /**
     * Test Case 2: Ensure exp uses unix timestamp that is in the future.
     *
     * Verifies that the expiration time is set correctly as a unix timestamp
     * in the future (approximately 1 hour from now).
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testJwtExpirationIsFuture()
    {
        // Skip if private key is not available
        if (! $this->checkRequiredConfig(['private_key'])) {
            $this->markTestSkipped('Private key is required for this test');
        }

        echo "\n\n✅ TEST CASE 2: Ensure exp uses unix timestamp that is in the future\n";

        // Get test config
        $config = Config::fromArray($this->getConfig());

        // Create HTTP client mock
        $httpClient = Mockery::mock(HttpClientInterface::class);

        // Create token manager
        $tokenManager = new TokenManager($config, $httpClient, new NullLogger());

        // Use reflection to access protected method
        $reflection = new ReflectionClass(TokenManager::class);
        $method     = $reflection->getMethod('generateAssertion');
        $method->setAccessible(true);

        // Get current time for comparison
        $now = time();

        // Generate JWT token
        $jwt = $method->invoke($tokenManager);

        // Decode payload
        $parts   = explode('.', $jwt);
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);

        // Verify exp is in the future
        $this->assertGreaterThan($now, $payload['exp'], 'Expiration time should be in the future');
        $expiresInSeconds = $payload['exp'] - $now;
        echo "✓ Expiration time is set to a future timestamp: {$payload['exp']} (expires in " .
        gmdate('H:i:s', $expiresInSeconds) . " from now)\n";

        // Verify exp is approximately 1 hour in the future
        $this->assertGreaterThanOrEqual($now + 3500, $payload['exp'], 'Expiration time should be ~1 hour in the future');
        $this->assertLessThanOrEqual($now + 3700, $payload['exp'], 'Expiration time should be ~1 hour in the future');
        echo "✓ Expiration time is set to approximately 1 hour in the future\n";
        echo "✓ Current time: " . date('Y-m-d H:i:s', $now) . "\n";
        echo "✓ Token expires: " . date('Y-m-d H:i:s', $payload['exp']) . "\n";
    }

    /**
     * Test Case 3: Keep Encoded JWT token to be used as assertion for Authentication Token API.
     *
     * Verifies that the JWT token is properly used as an assertion when making
     * the authentication request to the Pandago API.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testJwtTokenUsedForAuthentication()
    {
        // Skip if required config values are not available
        if (! $this->checkRequiredConfig(['client_id', 'key_id', 'scope', 'private_key'])) {
            $this->markTestSkipped('Client ID, Key ID, Scope, and Private Key are required for this test');
        }

        echo "\n\n✅ TEST CASE 3: Keep Encoded JWT token to be used as assertion for Authentication Token API\n";

        // Get test config
        $config = Config::fromArray($this->getConfig());

        // Create mock for the stream
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')
            ->once()
            ->andReturn(json_encode([
                'access_token' => 'test-token',
                'expires_in'   => 3600,
            ]));

        // Create mock for the response
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')
            ->once()
            ->andReturn(200);
        $response->shouldReceive('getBody')
            ->once()
            ->andReturn($stream);

        // Variable to capture the assertion token
        $capturedAssertion = null;

        // Create mock for the HTTP client
        $httpClient = Mockery::mock(HttpClientInterface::class);
        $httpClient->shouldReceive('request')
            ->once()
            ->with('POST', $config->getAuthUrl(), Mockery::on(function ($options) use (&$capturedAssertion) {
                // Capture the assertion for verification
                if (isset($options['form_params']['client_assertion'])) {
                    $capturedAssertion = $options['form_params']['client_assertion'];
                }

                // Verify the request contains the necessary JWT authentication parameters
                return isset($options['form_params']['client_assertion'])
                && isset($options['form_params']['client_assertion_type'])
                && 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer' === $options['form_params']['client_assertion_type'] && isset($options['form_params']['grant_type'])
                    && 'client_credentials' === $options['form_params']['grant_type'];
            }))
            ->andReturn($response);

        // Create token manager with mocked HTTP client
        $tokenManager = new TokenManager($config, $httpClient, new NullLogger());

        // Get token - this should use the JWT for authentication
        $token = $tokenManager->getToken();

        // Verify token was created correctly
        $this->assertInstanceOf(Token::class, $token);
        $this->assertEquals('test-token', $token->getAccessToken());
        $this->assertFalse($token->isExpired(0), 'Token should not be expired immediately after creation');

        // Verify that an assertion was captured and is properly formatted
        $this->assertNotNull($capturedAssertion, 'JWT assertion should have been captured');
        echo "✓ JWT token was properly used as assertion in the authentication request\n";

        // Verify the token structure
        $parts = explode('.', $capturedAssertion);
        $this->assertCount(3, $parts, 'JWT should have three parts: header, payload, signature');

        // Decode the payload to verify the expiration
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
        $this->assertArrayHasKey('exp', $payload, 'JWT payload should contain an exp claim');
        echo "✓ JWT assertion expires according to the exp value: " . date('Y-m-d H:i:s', $payload['exp']) . "\n";
        echo "✓ Access token successfully obtained using the JWT assertion\n";
    }

    /**
     * Test for consistent JWT generation.
     *
     * This test ensures that the non-random parts of the JWT (excluding jti)
     * remain consistent between generations with the same input parameters.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testConsistentJwtGeneration()
    {
        // Skip if private key is not available
        if (! $this->checkRequiredConfig(['private_key'])) {
            $this->markTestSkipped('Private key is required for this test');
        }

        // Get test config
        $config = Config::fromArray($this->getConfig());

        // Create HTTP client mock
        $httpClient = Mockery::mock(HttpClientInterface::class);

        // Create token manager
        $tokenManager = new TokenManager($config, $httpClient, new NullLogger());

        // Use reflection to access protected method
        $reflection = new ReflectionClass(TokenManager::class);
        $method     = $reflection->getMethod('generateAssertion');
        $method->setAccessible(true);

        // Generate two JWT tokens in succession
        $jwt1 = $method->invoke($tokenManager);

                       // Small delay to ensure timestamps would be different if not fixed
        usleep(10000); // 10ms delay

        $jwt2 = $method->invoke($tokenManager);

        // Decode payloads
        $parts1 = explode('.', $jwt1);
        $parts2 = explode('.', $jwt2);

        $payload1 = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts1[1])), true);
        $payload2 = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts2[1])), true);

        // Verify that issuer and subject are consistent
        $this->assertEquals($payload1['iss'], $payload2['iss'], 'Issuer should be consistent');
        $this->assertEquals($payload1['sub'], $payload2['sub'], 'Subject should be consistent');
        $this->assertEquals($payload1['aud'], $payload2['aud'], 'Audience should be consistent');

        // JWT ID should be different for each token
        $this->assertNotEquals($payload1['jti'], $payload2['jti'], 'JWT ID should be unique for each token');

        // Exp might be slightly different but should be close
        $this->assertLessThanOrEqual(1, abs($payload1['exp'] - $payload2['exp']), 'Expiration times should be close');
    }

    /**
     * Clean up after tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
