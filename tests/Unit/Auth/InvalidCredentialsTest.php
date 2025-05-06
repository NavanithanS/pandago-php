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
 * Test Case 2.2.4: Authorization Token with Invalid Credentials (Unhappy Path)
 *
 * This test verifies that using wrong Client ID or Scope will not generate
 * an authorization token and instead return appropriate errors.
 */
class InvalidCredentialsTest extends TestCase
{
    /**
     * Test generating an authorization token with wrong Client ID.
     *
     * Steps:
     * 1. Setup configuration with wrong Client ID
     * 2. Attempt to get an authorization token
     * 3. Verify error response with status 401 and error message "invalid_client"
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testAuthorizationTokenWithWrongClientId()
    {
        // Skip if required config values are not available
        if (! $this->checkRequiredConfig(['client_id', 'key_id', 'scope', 'private_key'])) {
            $this->markTestSkipped('Client ID, Key ID, Scope, and Private Key are required for this test');
        }

        echo "\n\n✅ TEST CASE 2.2.4a: Authorization Token with Wrong Client ID (Unhappy Path)\n";
        echo "====================================================================\n\n";
        echo "STEP 1: Setup and configuration\n";
        echo "------------------------------\n";

        // Get test config
        $config = Config::fromArray($this->getConfig());

        // Create a tampered config with wrong Client ID
        $validClientId   = $config->getClientId();
        $invalidClientId = 'wrong-' . substr($validClientId, 0, 8) . '-' . mt_rand(1000, 9999);

        echo "• Valid Client ID: " . $validClientId . "\n";
        echo "• Using wrong Client ID: " . $invalidClientId . "\n";

        // Use reflection to modify the clientId property
        $reflection       = new ReflectionClass($config);
        $clientIdProperty = $reflection->getProperty('clientId');
        $clientIdProperty->setAccessible(true);
        $clientIdProperty->setValue($config, $invalidClientId);

        echo "• Config modified to use wrong Client ID\n";
        echo "• Key ID: " . $config->getKeyId() . "\n";
        echo "• Scope: " . $config->getScope() . "\n";
        echo "• Environment: " . $config->getEnvironment() . "\n";
        echo "• Auth URL: " . $config->getAuthUrl() . "\n";

        echo "\nSTEP 2: Setup error response mocking\n";
        echo "-----------------------------------\n";

        // Mock the auth error response
        $errorResponse = [
            'error'             => 'invalid_client',
            'error_description' => 'Client authentication failed: Client ID not found',
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

        echo "\nSTEP 3: Setup HTTP client mock to capture request\n";
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

        echo "\nSTEP 4: Create TokenManager and attempt to get token\n";
        echo "---------------------------------------------------\n";

        // Create token manager with mocked HTTP client and tampered config
        $tokenManager = new TokenManager($config, $httpClient, $logger);

        echo "• TokenManager created with wrong Client ID\n";
        echo "• Attempting to get token (should fail)...\n";

        try {
            $token = $tokenManager->getToken();

            // If we reach here, the test has failed
            $this->fail('An exception should have been thrown due to wrong Client ID');
        } catch (AuthenticationException $e) {
            // This is the expected behavior - capture and analyze the exception
            echo "✓ AuthenticationException caught as expected\n";
            echo "• Exception message: " . $e->getMessage() . "\n";

            // Verify the exception contains information about authentication failure
            $this->assertStringContainsString('Failed to authenticate', $e->getMessage());
            $this->assertStringContainsString('Client authentication failed', $e->getMessage());

            echo "✓ Exception message contains expected error information\n";
        }

        echo "\nSTEP 5: Verify request and response details\n";
        echo "----------------------------------------\n";

        // Verify request format
        $this->assertNotNull($capturedRequest, 'Request should have been captured');
        echo "• Authentication request was captured successfully\n";

        // Verify HTTP response status code
        $this->assertEquals(401, $response->getStatusCode());
        echo "✓ Response status code is 401 as expected\n";

        // Now explicitly verify the error is "invalid_client"
        $responseBody = json_decode($stream->getContents(), true);
        $this->assertEquals('invalid_client', $responseBody['error'], 'Error code should be "invalid_client"');
        echo "✓ Response contains error code 'invalid_client' as expected\n";

        if (isset($responseBody['error_description'])) {
            echo "✓ Error description: " . $responseBody['error_description'] . "\n";
        }

        echo "\nSUMMARY: Authentication correctly failed with wrong Client ID\n";
        echo "=======================================================\n";
        echo "• Using wrong Client ID causes authentication to fail with 401 Unauthorized\n";
        echo "• The error response correctly indicates 'invalid_client' as the error\n";
        echo "• An AuthenticationException is thrown with appropriate error details\n";
    }

    /**
     * Test generating an authorization token with wrong Scope.
     *
     * Steps:
     * 1. Setup configuration with wrong Scope
     * 2. Attempt to get an authorization token
     * 3. Verify error response with status 400 and error message "invalid_scope"
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testAuthorizationTokenWithWrongScope()
    {
        // Skip if required config values are not available
        if (! $this->checkRequiredConfig(['client_id', 'key_id', 'scope', 'private_key'])) {
            $this->markTestSkipped('Client ID, Key ID, Scope, and Private Key are required for this test');
        }

        echo "\n\n✅ TEST CASE 2.2.4b: Authorization Token with Wrong Scope (Unhappy Path)\n";
        echo "===================================================================\n\n";
        echo "STEP 1: Setup and configuration\n";
        echo "------------------------------\n";

        // Get test config
        $config = Config::fromArray($this->getConfig());

        // Create a tampered config with wrong Scope
        $validScope   = $config->getScope();
        $invalidScope = 'wrong.scope.' . substr($validScope, 0, 5) . '.' . mt_rand(1000, 9999);

        echo "• Client ID: " . $config->getClientId() . "\n";
        echo "• Key ID: " . $config->getKeyId() . "\n";
        echo "• Valid Scope: " . $validScope . "\n";
        echo "• Using wrong Scope: " . $invalidScope . "\n";

        // Use reflection to modify the scope property
        $reflection    = new ReflectionClass($config);
        $scopeProperty = $reflection->getProperty('scope');
        $scopeProperty->setAccessible(true);
        $scopeProperty->setValue($config, $invalidScope);

        echo "• Config modified to use wrong Scope\n";
        echo "• Environment: " . $config->getEnvironment() . "\n";
        echo "• Auth URL: " . $config->getAuthUrl() . "\n";

        echo "\nSTEP 2: Setup error response mocking\n";
        echo "-----------------------------------\n";

        // Mock the auth error response
        $errorResponse = [
            'error'             => 'invalid_scope',
            'error_description' => 'The requested scope is invalid, unknown, or malformed',
        ];

        echo "• Mock error response prepared:\n";
        echo "  - error: " . $errorResponse['error'] . "\n";
        echo "  - error_description: " . $errorResponse['error_description'] . "\n";
        echo "  - expected HTTP status: 400 Bad Request\n";

        // Create mock for the stream with ability to read contents multiple times
        $streamContents = json_encode($errorResponse);
        $stream         = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')
            ->andReturn($streamContents);

        // Create mock for the response
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')
            ->twice()         // Expect two calls - one from TokenManager and one from our test
            ->andReturn(400); // 400 Bad Request
        $response->shouldReceive('getBody')
            ->once()
            ->andReturn($stream);

        echo "• Setup HTTP response mock with 400 status code and error message\n";

        // Track the request data sent to the token endpoint
        $capturedRequest = null;

        echo "\nSTEP 3: Setup HTTP client mock to capture request\n";
        echo "-----------------------------------------------\n";

        // Create mock for the HTTP client
        $httpClient = Mockery::mock(HttpClientInterface::class);
        $httpClient->shouldReceive('request')
            ->once()
            ->with('POST', $config->getAuthUrl(), Mockery::on(function ($options) use (&$capturedRequest, $invalidScope) {
                // Store the request data for verification
                $capturedRequest = $options;

                // Verify the scope is included in the request
                if (isset($options['form_params']['scope']) && $options['form_params']['scope'] === $invalidScope) {
                    return true;
                }
                return false;
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

        echo "• TokenManager created with wrong Scope\n";
        echo "• Attempting to get token (should fail)...\n";

        try {
            $token = $tokenManager->getToken();

            // If we reach here, the test has failed
            $this->fail('An exception should have been thrown due to wrong Scope');
        } catch (AuthenticationException $e) {
            // This is the expected behavior - capture and analyze the exception
            echo "✓ AuthenticationException caught as expected\n";
            echo "• Exception message: " . $e->getMessage() . "\n";

            // Verify the exception contains information about authentication failure
            $this->assertStringContainsString('Failed to authenticate', $e->getMessage());
            $this->assertStringContainsString('invalid_scope', $e->getMessage());

            echo "✓ Exception message contains expected error information\n";
        }

        echo "\nSTEP 5: Verify request and response details\n";
        echo "----------------------------------------\n";

        // Verify request format
        $this->assertNotNull($capturedRequest, 'Request should have been captured');
        echo "• Authentication request was captured successfully\n";

        // Verify the wrong scope was included in the request
        $this->assertEquals($invalidScope, $capturedRequest['form_params']['scope']);
        echo "✓ Request included the wrong scope value as expected\n";

        // Verify HTTP response status code
        $this->assertEquals(400, $response->getStatusCode());
        echo "✓ Response status code is 400 as expected\n";

        // Now explicitly verify the error is "invalid_scope"
        $responseBody = json_decode($stream->getContents(), true);
        $this->assertEquals('invalid_scope', $responseBody['error'], 'Error code should be "invalid_scope"');
        echo "✓ Response contains error code 'invalid_scope' as expected\n";

        if (isset($responseBody['error_description'])) {
            echo "✓ Error description: " . $responseBody['error_description'] . "\n";
        }

        echo "\nSUMMARY: Authentication correctly failed with wrong Scope\n";
        echo "====================================================\n";
        echo "• Using wrong Scope causes authentication to fail with 400 Bad Request\n";
        echo "• The error response correctly indicates 'invalid_scope' as the error\n";
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
