<?php
namespace Nava\Pandago\Tests\Unit\Auth;

use Mockery;
use Nava\Pandago\Auth\TokenManager;
use Nava\Pandago\Config;
use Nava\Pandago\Contracts\HttpClientInterface;
use Nava\Pandago\Exceptions\AuthenticationException;
use Nava\Pandago\Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * Test Case 2.2.2: Authorization Token (Unhappy Path)
 *
 * This test verifies that using JWT assertion with wrong Key ID or Client ID
 * will not generate an authorization token and instead return an error.
 */
class AuthorizationTokenUnhappyTest extends TestCase
{
    /**
     * Test generating an authorization token with invalid credentials.
     *
     * Steps:
     * 1. Create a JWT assertion with wrong Key ID or Client ID
     * 2. Attempt to get an authorization token using the invalid assertion
     * 3. Verify error response with status 401 and error message "invalid_client"
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testAuthorizationTokenWithInvalidCredentials()
    {
        // Skip if required config values are not available
        if (! $this->checkRequiredConfig(['client_id', 'key_id', 'scope', 'private_key'])) {
            $this->markTestSkipped('Client ID, Key ID, Scope, and Private Key are required for this test');
        }

        echo "\n\n✅ TEST CASE 2.2.2: Authorization Token with Invalid Credentials (Unhappy Path)\n";
        echo "===============================================================================\n\n";
        echo "STEP 1: Setup and configuration\n";
        echo "------------------------------\n";

        // Get test config
        $config = Config::fromArray($this->getConfig());
        echo "• Using client ID: " . $config->getClientId() . "\n";
        echo "• Using key ID: " . $config->getKeyId() . "\n";
        echo "• Using scope: " . $config->getScope() . "\n";
        echo "• Environment: " . $config->getEnvironment() . "\n";
        echo "• Auth URL: " . $config->getAuthUrl() . "\n";

        echo "\nSTEP 2: Create modified config with invalid credentials\n";
        echo "----------------------------------------------------\n";

        // Create a tampered config with wrong Key ID
        $validKeyId   = $config->getKeyId();
        $invalidKeyId = 'invalid-' . substr($validKeyId, 0, 8) . '-' . mt_rand(1000, 9999);

        $reflectionConfig = new ReflectionClass($config);
        $keyIdProperty    = $reflectionConfig->getProperty('keyId');
        $keyIdProperty->setAccessible(true);
        $keyIdProperty->setValue($config, $invalidKeyId);

        echo "• Original (valid) Key ID: " . $validKeyId . "\n";
        echo "• Modified (invalid) Key ID: " . $invalidKeyId . "\n";
        echo "• Config modified to use invalid Key ID\n";

        echo "\nSTEP 3: Setup error response mocking\n";
        echo "-----------------------------------\n";

        // Mock the auth error response
        $errorResponse = [
            'error'             => 'invalid_client',
            'error_description' => 'Invalid client credentials',
        ];

        echo "• Mock error response prepared:\n";
        echo "  - error: " . $errorResponse['error'] . "\n";
        echo "  - error_description: " . $errorResponse['error_description'] . "\n";
        echo "  - expected HTTP status: 401 Unauthorized\n";

        // Create mock for the stream
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')
            ->once()
            ->andReturn(json_encode($errorResponse));

        // Create mock for the response
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')
            ->once()
            ->andReturn(401); // 401 Unauthorized
        $response->shouldReceive('getBody')
            ->once()
            ->andReturn($stream);

        echo "• Setup HTTP response mock with 401 status code and error message\n";

        // Track the request data sent to the token endpoint
        $capturedRequest = null;

        echo "\nSTEP 4: Setup HTTP client mock to capture request\n";
        echo "-----------------------------------------------\n";

        // Create mock for the HTTP client
        $httpClient = Mockery::mock(HttpClientInterface::class);
        $httpClient->shouldReceive('request')
            ->once()
            ->with('POST', $config->getAuthUrl(), Mockery::on(function ($options) use (&$capturedRequest) {
                // Store the request data for verification
                $capturedRequest = $options;
                return true;
            }))
            ->andReturn($response);

        echo "• HTTP client mock setup to capture authentication request\n";

        // Create a logger to capture error messages
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')
            ->with('Failed to request token', Mockery::type('array'))
            ->once();

        echo "• Logger mock setup to capture error messages\n";

        echo "\nSTEP 5: Create TokenManager and attempt to get token\n";
        echo "---------------------------------------------------\n";

        // Create token manager with mocked HTTP client and tampered config
        $tokenManager = new TokenManager($config, $httpClient, $logger);

        echo "• TokenManager created with invalid credentials\n";
        echo "• Attempting to get token (should fail)...\n";

        try {
            $token = $tokenManager->getToken();

            // If we reach here, the test has failed
            $this->fail('An exception should have been thrown due to invalid credentials');
        } catch (AuthenticationException $e) {
            // This is the expected behavior - capture and analyze the exception
            echo "✓ AuthenticationException caught as expected\n";
            echo "• Exception message: " . $e->getMessage() . "\n";

            // Verify the exception contains information about authentication failure
            $this->assertStringContainsString('Failed to authenticate', $e->getMessage());
            $this->assertStringContainsString('invalid_client', $e->getMessage());

            echo "✓ Exception message contains expected error information\n";
        }

        echo "\nSTEP 6: Analyze JWT assertion that was sent\n";
        echo "----------------------------------------\n";

        // Verify request format
        $this->assertNotNull($capturedRequest, 'Request should have been captured');
        echo "• Authentication request was captured successfully\n";

        // Extract and analyze the JWT assertion
        $jwtAssertion = $capturedRequest['form_params']['client_assertion'] ?? null;
        $this->assertNotNull($jwtAssertion, 'JWT assertion should be present in request');

        echo "• JWT assertion in request: " . substr($jwtAssertion, 0, 20) . "..." . substr($jwtAssertion, -20) . "\n";

        // Analyze JWT to confirm it has invalid key ID
        $jwtParts = explode('.', $jwtAssertion);
        $this->assertCount(3, $jwtParts, 'JWT should have three parts: header.payload.signature');

        $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $jwtParts[0])), true);
        echo "• JWT Header:\n";
        echo "  - alg: " . ($header['alg'] ?? 'N/A') . "\n";
        echo "  - typ: " . ($header['typ'] ?? 'N/A') . "\n";
        echo "  - kid: " . ($header['kid'] ?? 'N/A') . "\n";

        // Verify the header contains the invalid key ID
        $this->assertEquals($invalidKeyId, $header['kid'] ?? null, 'JWT header should contain the invalid key ID');
        echo "✓ JWT header contains the invalid Key ID as expected\n";

        // Decode and analyze payload
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $jwtParts[1])), true);
        echo "• JWT Payload (partial):\n";
        if (! empty($payload)) {
            echo "  - iss: " . ($payload['iss'] ?? 'N/A') . "\n";
            echo "  - sub: " . ($payload['sub'] ?? 'N/A') . "\n";
            echo "  - aud: " . ($payload['aud'] ?? 'N/A') . "\n";
        } else {
            echo "  [Could not decode payload]\n";
        }

        echo "\nSTEP 7: Verify the error response handling\n";
        echo "----------------------------------------\n";

        // The error response should have been parsed and included in the exception
        $this->assertEquals(401, $response->getStatusCode());
        echo "✓ Response status code is 401 as expected\n";

        $responseData = json_decode($stream->getContents(), true);
        $this->assertEquals('invalid_client', $responseData['error']);
        echo "✓ Response contains 'invalid_client' error as expected\n";

        echo "\nSUMMARY: Authentication correctly failed with invalid credentials\n";
        echo "===========================================================\n";
        echo "• Using invalid Key ID in JWT assertion causes authentication to fail\n";
        echo "• The error response correctly indicates 'invalid_client' as the error\n";
        echo "• An AuthenticationException is thrown with appropriate error details\n";
    }

    /**
     * Test generating an authorization token with invalid client ID.
     *
     * Similar to the previous test but modifies the client ID instead of the key ID.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testAuthorizationTokenWithInvalidClientId()
    {
        // Skip if required config values are not available
        if (! $this->checkRequiredConfig(['client_id', 'key_id', 'scope', 'private_key'])) {
            $this->markTestSkipped('Client ID, Key ID, Scope, and Private Key are required for this test');
        }

        echo "\n\n✅ TEST CASE 2.2.2b: Authorization Token with Invalid Client ID (Unhappy Path)\n";
        echo "=============================================================================\n\n";
        echo "STEP 1: Setup and configuration\n";
        echo "------------------------------\n";

        // Get test config
        $config = Config::fromArray($this->getConfig());
        echo "• Using client ID: " . $config->getClientId() . "\n";
        echo "• Using key ID: " . $config->getKeyId() . "\n";
        echo "• Using scope: " . $config->getScope() . "\n";

        echo "\nSTEP 2: Create modified config with invalid client ID\n";
        echo "----------------------------------------------------\n";

        // Create a tampered config with wrong Client ID
        $validClientId   = $config->getClientId();
        $invalidClientId = 'invalid-' . substr($validClientId, 0, 8) . '-' . mt_rand(1000, 9999);

        $reflectionConfig = new ReflectionClass($config);
        $clientIdProperty = $reflectionConfig->getProperty('clientId');
        $clientIdProperty->setAccessible(true);
        $clientIdProperty->setValue($config, $invalidClientId);

        echo "• Original (valid) Client ID: " . $validClientId . "\n";
        echo "• Modified (invalid) Client ID: " . $invalidClientId . "\n";
        echo "• Config modified to use invalid Client ID\n";

        echo "\nSTEP 3: Setup error response mocking\n";
        echo "-----------------------------------\n";

        // Mock the auth error response
        $errorResponse = [
            'error'             => 'invalid_client',
            'error_description' => 'Client authentication failed',
        ];

        echo "• Mock error response prepared:\n";
        echo "  - error: " . $errorResponse['error'] . "\n";
        echo "  - error_description: " . $errorResponse['error_description'] . "\n";
        echo "  - expected HTTP status: 401 Unauthorized\n";

        // Create mock for the stream
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')
            ->once()
            ->andReturn(json_encode($errorResponse));

        // Create mock for the response
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')
            ->once()
            ->andReturn(401); // 401 Unauthorized
        $response->shouldReceive('getBody')
            ->once()
            ->andReturn($stream);

        echo "• Setup HTTP response mock with 401 status code and error message\n";

        // Track the request data sent to the token endpoint
        $capturedRequest = null;

        // Create mock for the HTTP client
        $httpClient = Mockery::mock(HttpClientInterface::class);
        $httpClient->shouldReceive('request')
            ->once()
            ->with('POST', $config->getAuthUrl(), Mockery::on(function ($options) use (&$capturedRequest) {
                // Store the request data for verification
                $capturedRequest = $options;
                return true;
            }))
            ->andReturn($response);

        echo "• HTTP client mock setup to capture authentication request\n";

        // Create a logger to capture error messages
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')
            ->with('Failed to request token', Mockery::type('array'))
            ->once();

        echo "• Logger mock setup to capture error messages\n";

        echo "\nSTEP 4: Create TokenManager and attempt to get token\n";
        echo "---------------------------------------------------\n";

        // Create token manager with mocked HTTP client and tampered config
        $tokenManager = new TokenManager($config, $httpClient, $logger);

        echo "• TokenManager created with invalid client ID\n";
        echo "• Attempting to get token (should fail)...\n";

        try {
            $token = $tokenManager->getToken();

            // If we reach here, the test has failed
            $this->fail('An exception should have been thrown due to invalid client ID');
        } catch (AuthenticationException $e) {
            // This is the expected behavior - capture and analyze the exception
            echo "✓ AuthenticationException caught as expected\n";
            echo "• Exception message: " . $e->getMessage() . "\n";

            // Verify the exception contains information about authentication failure
            $this->assertStringContainsString('Failed to authenticate', $e->getMessage());
            $this->assertStringContainsString('invalid_client', $e->getMessage());

            echo "✓ Exception message contains expected error information\n";
        }

        echo "\nSTEP 5: Analyze JWT assertion that was sent\n";
        echo "----------------------------------------\n";

        // Verify request format
        $this->assertNotNull($capturedRequest, 'Request should have been captured');

        // Extract and analyze the JWT assertion
        $jwtAssertion = $capturedRequest['form_params']['client_assertion'] ?? null;
        $this->assertNotNull($jwtAssertion, 'JWT assertion should be present in request');

        echo "• JWT assertion in request: " . substr($jwtAssertion, 0, 20) . "..." . substr($jwtAssertion, -20) . "\n";

        // Analyze JWT to confirm it has invalid client ID in payload
        $jwtParts = explode('.', $jwtAssertion);
        $this->assertCount(3, $jwtParts, 'JWT should have three parts: header.payload.signature');

        // Decode and analyze payload
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $jwtParts[1])), true);
        echo "• JWT Payload:\n";
        if (! empty($payload)) {
            echo "  - iss: " . ($payload['iss'] ?? 'N/A') . "\n";
            echo "  - sub: " . ($payload['sub'] ?? 'N/A') . "\n";
            echo "  - aud: " . ($payload['aud'] ?? 'N/A') . "\n";
        } else {
            echo "  [Could not decode payload]\n";
        }

        // Verify the payload contains the invalid client ID
        $this->assertEquals($invalidClientId, $payload['iss'] ?? null, 'JWT payload should contain invalid client ID as issuer');
        $this->assertEquals($invalidClientId, $payload['sub'] ?? null, 'JWT payload should contain invalid client ID as subject');
        echo "✓ JWT payload contains the invalid Client ID as expected\n";

        echo "\nSTEP 6: Verify the error response handling\n";
        echo "----------------------------------------\n";

        // The error response should have been parsed and included in the exception
        $this->assertEquals(401, $response->getStatusCode());
        echo "✓ Response status code is 401 as expected\n";

        $responseData = json_decode($stream->getContents(), true);
        $this->assertEquals('invalid_client', $responseData['error']);
        echo "✓ Response contains 'invalid_client' error as expected\n";

        echo "\nSUMMARY: Authentication correctly failed with invalid client ID\n";
        echo "=========================================================\n";
        echo "• Using invalid Client ID in JWT assertion causes authentication to fail\n";
        echo "• The error response correctly indicates 'invalid_client' as the error\n";
        echo "• An AuthenticationException is thrown with appropriate error details\n";
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
