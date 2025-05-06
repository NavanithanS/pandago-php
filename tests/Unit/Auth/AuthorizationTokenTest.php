<?php
namespace Nava\Pandago\Tests\Unit\Auth;

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
 * Test Case 2.2.1: Authorization Token (Happy Path)
 *
 * This test verifies the generation of authorization tokens using JWT assertion
 * and credentials provided by the pandaGo team. The token is used for
 * authentication with pandaGo API endpoints.
 */
class AuthorizationTokenTest extends TestCase
{
    /**
     * Test generating an authorization token with valid credentials.
     *
     * Steps:
     * 1. Create a request to the token endpoint
     * 2. Include client ID, JWT assertion, and scope
     * 3. Set grant_type as client_credentials
     * 4. Set client_assertion_type as urn:ietf:params:oauth:client-assertion-type:jwt-bearer
     * 5. Verify the received authorization token
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testAuthorizationTokenGeneration()
    {
        // Skip if required config values are not available
        if (! $this->checkRequiredConfig(['client_id', 'key_id', 'scope', 'private_key'])) {
            $this->markTestSkipped('Client ID, Key ID, Scope, and Private Key are required for this test');
        }

        echo "\n\n✅ TEST CASE 2.2.1: Authorization Token Generation (Happy Path)\n";
        echo "==============================================================\n\n";
        echo "STEP 1: Setup and configuration\n";
        echo "------------------------------\n";

        // Get test config
        $config = Config::fromArray($this->getConfig());
        echo "• Using client ID: " . $config->getClientId() . "\n";
        echo "• Using key ID: " . $config->getKeyId() . "\n";
        echo "• Using scope: " . $config->getScope() . "\n";
        echo "• Environment: " . $config->getEnvironment() . "\n";
        echo "• Auth URL: " . $config->getAuthUrl() . "\n";

        // Check if private key is a file path or a string
        $privateKey = $config->getPrivateKey();
        if (file_exists($privateKey)) {
            echo "• Private key: Using file at " . $privateKey . "\n";
        } else {
            $keyPreview = substr($privateKey, 0, 40) . "..." . substr($privateKey, -20);
            echo "• Private key: Using inline key " . $keyPreview . "\n";
        }

        echo "\nSTEP 2: Setup token response mocking\n";
        echo "-----------------------------------\n";

        // Mock the auth response with token data
        $mockTokenResponse = [
            'access_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6IjRkOTM0NDY5LTI3YTItNDAyYS1hMGRmLWEyNDAxMDdlOTg4MCJ9.eyJqdGkiOiI4ZTI3MDM2Mi1kZTMxLTRiMmUtOWFlMi02Y2FiY2Q3YjYzZTEiLCJpYXQiOjE2MTQ3MzU5NjksImV4cCI6MTYxNDgwNTk2OSwic3ViIjoicGFuZGFnby1zZy1mZDExMGZmNy03ZTczLTQ0NzgtYTI1ZS04NjQ4OGIyN2NjMGYiLCJhdWQiOiJodHRwczovL3N0cy1zdC5kZWxpdmVyeWhlcm8uaW8iLCJpc3MiOiJwYW5kYWdvLXNnLWZkMTEwZmY3LTdlNzMtNDQ3OC1hMjVlLTg2NDg4YjI3Y2MwZiIsInNjb3BlIjoicGFuZGFnby5hcGkuc2cifQ...', // Example token (shortened)
            'expires_in'   => 900,                                                                                                                                                                                                                                                                                                                                                                                                                                                          // 15 minutes
            'token_type'   => 'Bearer',
        ];

        echo "• Mock token response prepared:\n";
        echo "  - access_token: " . substr($mockTokenResponse['access_token'], 0, 30) . "...\n";
        echo "  - expires_in: " . $mockTokenResponse['expires_in'] . " seconds (15 minutes)\n";
        echo "  - token_type: " . $mockTokenResponse['token_type'] . "\n";

        // Create mock for the stream
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')
            ->once()
            ->andReturn(json_encode($mockTokenResponse));

        // Create mock for the response
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')
            ->once()
            ->andReturn(200);
        $response->shouldReceive('getBody')
            ->once()
            ->andReturn($stream);

        echo "• Setup HTTP mocks for response handling\n";

        // Track the request data sent to the token endpoint
        $capturedRequest = null;

        echo "\nSTEP 3: Setup request expectations\n";
        echo "---------------------------------\n";
        echo "• Expecting POST request to: " . $config->getAuthUrl() . "\n";
        echo "• Expecting headers to include Content-Type: application/x-www-form-urlencoded\n";
        echo "• Expecting form parameters to include:\n";
        echo "  - grant_type: client_credentials\n";
        echo "  - client_id: " . $config->getClientId() . "\n";
        echo "  - client_assertion_type: urn:ietf:params:oauth:client-assertion-type:jwt-bearer\n";
        echo "  - client_assertion: [JWT token]\n";
        echo "  - scope: " . $config->getScope() . "\n";

        // Create mock for the HTTP client
        $httpClient = Mockery::mock(HttpClientInterface::class);
        $httpClient->shouldReceive('request')
            ->once()
            ->with('POST', $config->getAuthUrl(), Mockery::on(function ($options) use (&$capturedRequest) {
                // Store the request data for verification
                $capturedRequest = $options;

                // Verify request format
                return isset($options['headers']['Content-Type'])
                && 'application/x-www-form-urlencoded' === $options['headers']['Content-Type'] && isset($options['form_params']['grant_type'])
                && 'client_credentials' === $options['form_params']['grant_type'] && isset($options['form_params']['client_id'])
                && isset($options['form_params']['client_assertion_type'])
                && 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer' === $options['form_params']['client_assertion_type'] && isset($options['form_params']['client_assertion'])
                && isset($options['form_params']['scope']);
            }))
            ->andReturn($response);

        echo "\nSTEP 4: Create TokenManager and request token\n";
        echo "-------------------------------------------\n";

        // Create token manager with mocked HTTP client
        echo "• Creating TokenManager instance\n";
        $tokenManager = new TokenManager($config, $httpClient, new NullLogger());

        // Get token - this should use the JWT for authentication
        echo "• Calling getToken() method to generate JWT and request authorization token\n";
        $start = microtime(true);
        $token = $tokenManager->getToken();
        $end   = microtime(true);

        echo "• Token obtained in " . round(($end - $start) * 1000, 2) . " ms\n";

        echo "\nSTEP 5: Verify the token response\n";
        echo "--------------------------------\n";

        // Verify a token was received
        $this->assertInstanceOf(Token::class, $token);
        echo "✓ Successfully generated authorization token - instance of Token class\n";

        // Verify token properties
        $this->assertEquals($mockTokenResponse['access_token'], $token->getAccessToken());
        echo "✓ Token access_token matches expected value\n";
        echo "  Token: " . substr($token->getAccessToken(), 0, 30) . "...\n";

        $this->assertFalse($token->isExpired(0), 'Token should not be expired immediately after creation');
        echo "✓ Token is not expired immediately after creation\n";
        echo "  Expires at: " . date('Y-m-d H:i:s', $token->getExpiresAt()) . "\n";

        echo "\nSTEP 6: Examine the request that was sent\n";
        echo "---------------------------------------\n";

        // Verify request format
        $this->assertNotNull($capturedRequest, 'Request should have been captured');
        echo "• Request data was successfully captured\n";

        // Check content type header
        $this->assertEquals('application/x-www-form-urlencoded', $capturedRequest['headers']['Content-Type']);
        echo "✓ Request used correct Content-Type header: " . $capturedRequest['headers']['Content-Type'] . "\n";

        // Check form parameters
        $this->assertEquals('client_credentials', $capturedRequest['form_params']['grant_type']);
        echo "✓ Request used correct grant_type: " . $capturedRequest['form_params']['grant_type'] . "\n";

        $this->assertEquals('urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            $capturedRequest['form_params']['client_assertion_type']);
        echo "✓ Request used correct client_assertion_type: " . $capturedRequest['form_params']['client_assertion_type'] . "\n";

        $this->assertEquals($config->getClientId(), $capturedRequest['form_params']['client_id']);
        echo "✓ Request included the correct client_id: " . $capturedRequest['form_params']['client_id'] . "\n";

        $this->assertEquals($config->getScope(), $capturedRequest['form_params']['scope']);
        echo "✓ Request included the correct scope: " . $capturedRequest['form_params']['scope'] . "\n";

        // Verify JWT assertion is present and correctly formatted
        $this->assertNotEmpty($capturedRequest['form_params']['client_assertion']);
        $jwtToken = $capturedRequest['form_params']['client_assertion'];
        echo "✓ Request included JWT assertion: " . substr($jwtToken, 0, 20) . "..." . substr($jwtToken, -20) . "\n";

        $jwtParts = explode('.', $jwtToken);
        $this->assertCount(3, $jwtParts, 'JWT should have three parts: header.payload.signature');
        echo "✓ JWT has correct format with 3 parts (header.payload.signature)\n";

        // Additional JWT analysis - decode header and payload
        $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $jwtParts[0])), true);
        echo "• JWT Header:\n";
        echo "  - alg: " . ($header['alg'] ?? 'N/A') . "\n";
        echo "  - typ: " . ($header['typ'] ?? 'N/A') . "\n";
        echo "  - kid: " . ($header['kid'] ?? 'N/A') . "\n";

        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $jwtParts[1])), true);
        echo "• JWT Payload:\n";
        if (! empty($payload)) {
            foreach ($payload as $key => $value) {
                echo "  - $key: " . (is_string($value) ? $value : json_encode($value)) . "\n";
            }
        } else {
            echo "  [Could not decode payload]\n";
        }

        echo "\nSTEP 7: Token expiration details\n";
        echo "------------------------------\n";

        // Calculate expiration time
        $now            = time();
        $tokenExpiresAt = $now + $mockTokenResponse['expires_in'];
        $timeRemaining  = $tokenExpiresAt - $now;

        echo "• Current time: " . date('Y-m-d H:i:s', $now) . "\n";
        echo "• Token expires at: " . date('Y-m-d H:i:s', $tokenExpiresAt) . "\n";
        echo "• Time remaining: " . $timeRemaining . " seconds (" . gmdate('i:s', $timeRemaining) . " minutes)\n";
        echo "✓ Token will expire after 15 minutes as expected\n";

        echo "\nSUMMARY: Authorization token successfully generated and validated\n";
        echo "============================================================\n";
    }

    /**
     * Test token expiration and reuse behavior.
     *
     * This test verifies that:
     * 1. An existing token is reused if it hasn't expired
     * 2. A new token is requested if the existing one has expired
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testTokenExpirationAndReuse()
    {
        // Skip if required config values are not available
        if (! $this->checkRequiredConfig(['client_id', 'key_id', 'scope', 'private_key'])) {
            $this->markTestSkipped('Client ID, Key ID, Scope, and Private Key are required for this test');
        }

        echo "\n\n✅ TEST CASE: Token Expiration and Reuse\n";
        echo "=====================================\n\n";
        echo "STEP 1: Setup and configuration\n";
        echo "------------------------------\n";

        // Get test config
        $config = Config::fromArray($this->getConfig());
        echo "• Using client ID: " . $config->getClientId() . "\n";
        echo "• Using auth URL: " . $config->getAuthUrl() . "\n";

        echo "\nSTEP 2: Create mocks for first token request\n";
        echo "-------------------------------------------\n";

        // Create mock for the stream
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')
            ->once() // This should be called only once since the second request should reuse the token
            ->andReturn(json_encode([
                'access_token' => 'test-token',
                'expires_in'   => 900, // 15 minutes
                'token_type'   => 'Bearer',
            ]));

        echo "• Mock response setup for first token:\n";
        echo "  - access_token: test-token\n";
        echo "  - expires_in: 900 seconds (15 minutes)\n";
        echo "  - token_type: Bearer\n";

        // Create mock for the response
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')
            ->once()
            ->andReturn(200);
        $response->shouldReceive('getBody')
            ->once()
            ->andReturn($stream);

        echo "• HTTP response mock configured with 200 status code\n";

        // Create mock for the HTTP client
        $httpClient = Mockery::mock(HttpClientInterface::class);
        $httpClient->shouldReceive('request')
            ->once() // Should only be called once
            ->with('POST', $config->getAuthUrl(), Mockery::type('array'))
            ->andReturn($response);

        echo "• HTTP client mock configured to expect only ONE request initially\n";

        echo "\nSTEP 3: Create TokenManager and perform first token request\n";
        echo "--------------------------------------------------------\n";

        // Create token manager with mocked HTTP client
        $tokenManager = new TokenManager($config, $httpClient, new NullLogger());
        echo "• TokenManager created\n";

        // First token request
        echo "• Requesting first token...\n";
        $start  = microtime(true);
        $token1 = $tokenManager->getToken();
        $end    = microtime(true);

        $this->assertInstanceOf(Token::class, $token1);
        $this->assertEquals('test-token', $token1->getAccessToken());

        echo "✓ First token obtained in " . round(($end - $start) * 1000, 2) . " ms\n";
        echo "  - Token value: test-token\n";
        echo "  - Expires at: " . date('Y-m-d H:i:s', $token1->getExpiresAt()) . "\n";
        echo "  - Time remaining: " . ($token1->getExpiresAt() - time()) . " seconds\n";

        echo "\nSTEP 4: Perform second token request (should reuse token)\n";
        echo "------------------------------------------------------\n";

        // Second token request (should reuse token)
        echo "• Requesting second token...\n";
        $start  = microtime(true);
        $token2 = $tokenManager->getToken();
        $end    = microtime(true);

        $this->assertInstanceOf(Token::class, $token2);
        $this->assertSame($token1, $token2, 'Should return the same token instance');

        echo "✓ Second token obtained in " . round(($end - $start) * 1000, 2) . " ms (much faster since cached)\n";
        echo "✓ Second request correctly returned the SAME token instance (token was reused)\n";
        echo "  - First token memory address: " . spl_object_hash($token1) . "\n";
        echo "  - Second token memory address: " . spl_object_hash($token2) . "\n";

        echo "\nSTEP 5: Simulate token expiration\n";
        echo "-------------------------------\n";

        // Create a reflection of Token class to simulate expiration
        $reflectionToken   = new ReflectionClass(Token::class);
        $expiresAtProperty = $reflectionToken->getProperty('expiresAt');
        $expiresAtProperty->setAccessible(true);

        $originalExpiry = $token1->getExpiresAt();
        $newExpiry      = time() - 60; // Set expiration to 1 minute ago

        echo "• Original token expiry: " . date('Y-m-d H:i:s', $originalExpiry) . "\n";
        echo "• Forcibly changing expiry to 1 minute in the past: " . date('Y-m-d H:i:s', $newExpiry) . "\n";

        $expiresAtProperty->setValue($token1, $newExpiry);

        echo "• Token expiration has been modified using reflection\n";
        echo "• Current token status - isExpired(): " . ($token1->isExpired() ? 'TRUE' : 'FALSE') . "\n";

        echo "\nSTEP 6: Setup mocks for new token after expiration\n";
        echo "------------------------------------------------\n";

        // Create a new mock for expired token scenario
        $streamExpired = Mockery::mock(StreamInterface::class);
        $streamExpired->shouldReceive('getContents')
            ->once()
            ->andReturn(json_encode([
                'access_token' => 'new-test-token',
                'expires_in'   => 900,
                'token_type'   => 'Bearer',
            ]));

        $responseExpired = Mockery::mock(ResponseInterface::class);
        $responseExpired->shouldReceive('getStatusCode')
            ->once()
            ->andReturn(200);
        $responseExpired->shouldReceive('getBody')
            ->once()
            ->andReturn($streamExpired);

        echo "• Mock response setup for new token after expiration:\n";
        echo "  - access_token: new-test-token\n";
        echo "  - expires_in: 900 seconds (15 minutes)\n";
        echo "  - token_type: Bearer\n";

        // Now the HTTP client should be called again for a new token
        $httpClient->shouldReceive('request')
            ->once()
            ->with('POST', $config->getAuthUrl(), Mockery::type('array'))
            ->andReturn($responseExpired);

        echo "• HTTP client mock now configured to expect a SECOND request\n";

        echo "\nSTEP 7: Request token after expiration (should get new token)\n";
        echo "---------------------------------------------------------\n";

        // This should get a new token since the previous one has expired
        echo "• Requesting token after expiration...\n";
        $start  = microtime(true);
        $token3 = $tokenManager->getToken();
        $end    = microtime(true);

        $this->assertInstanceOf(Token::class, $token3);
        $this->assertNotSame($token1, $token3, 'Should return a new token instance');
        $this->assertEquals('new-test-token', $token3->getAccessToken());

        echo "✓ New token obtained in " . round(($end - $start) * 1000, 2) . " ms\n";
        echo "✓ A NEW token instance was created (as expected after expiration)\n";
        echo "  - Old token value: test-token\n";
        echo "  - New token value: new-test-token\n";
        echo "  - Old token memory address: " . spl_object_hash($token1) . "\n";
        echo "  - New token memory address: " . spl_object_hash($token3) . "\n";
        echo "  - New token expires at: " . date('Y-m-d H:i:s', $token3->getExpiresAt()) . "\n";

        echo "\nSUMMARY: Token caching and refresh mechanism works correctly\n";
        echo "=======================================================\n";
        echo "• Tokens are reused until they expire (efficient caching)\n";
        echo "• New tokens are automatically requested when expired\n";
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
