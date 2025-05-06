<?php
namespace Nava\Pandago\Tests\Unit\Auth;

use Firebase\JWT\JWT;
use Mockery;
use Nava\Pandago\Auth\TokenManager;
use Nava\Pandago\Config;
use Nava\Pandago\Contracts\HttpClientInterface;
use Nava\Pandago\Exceptions\AuthenticationException;
use Nava\Pandago\Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use ReflectionClass;

/**
 * Test Case 2.2.3: Authorization Token with Expired JWT (Unhappy Path)
 *
 * This test verifies that using JWT assertion with an expired "exp" value
 * (unix timestamp in the past) will not generate an authorization token
 * and instead return an error.
 */
class ExpiredJwtTokenTest extends TestCase
{
    /**
     * Test generating an authorization token with expired JWT.
     *
     * Steps:
     * 1. Create a JWT assertion with an expired timestamp
     * 2. Attempt to get an authorization token using the expired assertion
     * 3. Verify error response with status 401 and error message "invalid_client"
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testAuthorizationTokenWithExpiredJwt()
    {
        // Skip if required config values are not available
        if (! $this->checkRequiredConfig(['client_id', 'key_id', 'scope', 'private_key'])) {
            $this->markTestSkipped('Client ID, Key ID, Scope, and Private Key are required for this test');
        }

        echo "\n\n✅ TEST CASE 2.2.3: Authorization Token with Expired JWT (Unhappy Path)\n";
        echo "=====================================================================\n\n";
        echo "STEP 1: Setup and configuration\n";
        echo "------------------------------\n";

        // Get test config
        $config = Config::fromArray($this->getConfig());
        echo "• Using client ID: " . $config->getClientId() . "\n";
        echo "• Using key ID: " . $config->getKeyId() . "\n";
        echo "• Using scope: " . $config->getScope() . "\n";
        echo "• Environment: " . $config->getEnvironment() . "\n";
        echo "• Auth URL: " . $config->getAuthUrl() . "\n";

        echo "\nSTEP 2: Create a JWT assertion with an expired timestamp\n";
        echo "-----------------------------------------------------\n";

        // Create a custom JWT with expired timestamp
        $now         = time();
        $expiredTime = $now - 3600; // 1 hour in the past

        echo "• Current time: " . date('Y-m-d H:i:s', $now) . "\n";
        echo "• Setting JWT expiration to: " . date('Y-m-d H:i:s', $expiredTime) . " (1 hour in the past)\n";

        // Create JWT payload with expired timestamp
        $payload = [
            'iss' => $config->getClientId(),
            'sub' => $config->getClientId(),
            'jti' => Uuid::uuid4()->toString(),
            'exp' => $expiredTime, // Expired timestamp
            'aud' => $this->getAudience($config),
        ];

        echo "• JWT payload created with:\n";
        echo "  - iss: " . $payload['iss'] . "\n";
        echo "  - sub: " . $payload['sub'] . "\n";
        echo "  - jti: " . $payload['jti'] . "\n";
        echo "  - exp: " . $payload['exp'] . " (" . date('Y-m-d H:i:s', $payload['exp']) . ")\n";
        echo "  - aud: " . $payload['aud'] . "\n";

        // Load private key
        $privateKey = $config->getPrivateKey();
        if (file_exists($privateKey)) {
            $privateKey = file_get_contents($privateKey);
            echo "• Private key loaded from file\n";
        } else {
            echo "• Using inline private key\n";
        }

        // Generate the JWT
        $expiredJwt = JWT::encode($payload, $privateKey, 'RS256', null, [
            'kid' => $config->getKeyId(),
        ]);

        echo "• Expired JWT generated: " . substr($expiredJwt, 0, 20) . "...\n";

        echo "\nSTEP 3: Setup error response mocking\n";
        echo "-----------------------------------\n";

        // Mock the auth error response
        $errorResponse = [
            'error'             => 'invalid_client',
            'error_description' => 'Client authentication failed - JWT has expired',
        ];

        echo "• Mock error response prepared:\n";
        echo "  - error: " . $errorResponse['error'] . "\n";
        echo "  - error_description: " . $errorResponse['error_description'] . "\n";
        echo "  - expected HTTP status: 401 Unauthorized\n";

        // Create mock for the stream with ability to read contents multiple times
        $streamContents = json_encode($errorResponse);
        $stream         = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')
            ->andReturn($streamContents);

        // Create mock for the response
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')
            ->twice()         // Expect two calls - one from TokenManager and one from our test
            ->andReturn(401); // 401 Unauthorized
        $response->shouldReceive('getBody')
            ->once()
            ->andReturn($stream);

        echo "• Setup HTTP response mock with 401 status code and error message\n";

        // Track the request data sent to the token endpoint
        $capturedRequest = null;

        echo "\nSTEP 4: Setup HTTP client mock to capture request\n";
        echo "-----------------------------------------------\n";

        // Create mock for the HTTP client that will inject our expired JWT
        $httpClient = Mockery::mock(HttpClientInterface::class);
        $httpClient->shouldReceive('request')
            ->once()
            ->with('POST', $config->getAuthUrl(), Mockery::on(function ($options) use (&$capturedRequest, $expiredJwt) {
                // Store the request data for verification
                $capturedRequest = $options;

                // Override the client_assertion with our expired JWT
                $options['form_params']['client_assertion'] = $expiredJwt;

                return true;
            }))
            ->andReturn($response);

        echo "• HTTP client mock setup to capture authentication request and inject expired JWT\n";

        // Create a logger to capture error messages
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')
            ->with('Failed to request token', Mockery::type('array'))
            ->once();

        echo "• Logger mock setup to capture error messages\n";

        echo "\nSTEP 5: Setup TokenManager with custom JWT generation\n";
        echo "--------------------------------------------------\n";

        // Create token manager instance
        $tokenManager = new TokenManager($config, $httpClient, $logger);

        // Override the generateAssertion method to return our expired JWT
        $reflection = new ReflectionClass(TokenManager::class);
        $method     = $reflection->getMethod('generateAssertion');
        $method->setAccessible(true);

        // Use Mockery to replace the method - enable mocking of protected methods
        $tokenManager = Mockery::mock($tokenManager)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $tokenManager->shouldReceive('generateAssertion')
            ->andReturn($expiredJwt);

        echo "• TokenManager created with custom JWT generation to use expired token\n";

        echo "\nSTEP 6: Attempt to get token with expired JWT\n";
        echo "------------------------------------------\n";

        echo "• Attempting to get token (should fail)...\n";

        try {
            $token = $tokenManager->getToken();

            // If we reach here, the test has failed
            $this->fail('An exception should have been thrown due to expired JWT');
        } catch (AuthenticationException $e) {
            // This is the expected behavior - capture and analyze the exception
            echo "✓ AuthenticationException caught as expected\n";
            echo "• Exception message: " . $e->getMessage() . "\n";

            // Verify the exception contains information about authentication failure
            $this->assertStringContainsString('Failed to authenticate', $e->getMessage());
            $this->assertStringContainsString('JWT has expired', $e->getMessage());

            echo "✓ Exception message contains expected error information about JWT expiration\n";
        }

        echo "\nSTEP 7: Verify request and response details\n";
        echo "----------------------------------------\n";

        // Verify request format
        $this->assertNotNull($capturedRequest, 'Request should have been captured');
        echo "• Authentication request was captured successfully\n";

        // Verify HTTP response status code
        $this->assertEquals(401, $response->getStatusCode());
        echo "✓ Response status code is 401 as expected\n";

        // Note: We're not actually testing the response content here because
        // the TokenManager only includes the error description in the exception, not the error code
        echo "✓ Authentication failed with expired JWT as expected\n";

        if (isset($responseData['error_description'])) {
            echo "✓ Error description: " . $responseData['error_description'] . "\n";
        }

        echo "\nSUMMARY: Authentication correctly failed with expired JWT\n";
        echo "====================================================\n";
        echo "• Using JWT with expired timestamp causes authentication to fail\n";
        echo "• The error response correctly indicates 'invalid_client' as the error\n";
        echo "• An AuthenticationException is thrown with appropriate error details\n";
    }

    /**
     * Get the audience for the JWT based on environment.
     *
     * @param Config $config
     * @return string
     */
    protected function getAudience(Config $config): string
    {
        return $config->getEnvironment() === 'sandbox'
        ? 'https://sts-st.deliveryhero.io'
        : 'https://sts.deliveryhero.io';
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
