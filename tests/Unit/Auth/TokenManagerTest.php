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

class TokenManagerTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testGetToken()
    {
        // Use mock to avoid actual JWT signing
        $this->mockJwtEncoding();

        $config = Config::fromArray($this->getConfig());
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')
            ->once()
            ->andReturn(json_encode([
                'access_token' => 'test-token',
                'expires_in'   => 3600,
            ]));

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')
            ->once()
            ->andReturn(200);
        $response->shouldReceive('getBody')
            ->once()
            ->andReturn($stream);

        $httpClient = Mockery::mock(HttpClientInterface::class);
        $httpClient->shouldReceive('request')
            ->once()
            ->with('POST', $config->getAuthUrl(), Mockery::type('array'))
            ->andReturn($response);

        $tokenManager = new TokenManager($config, $httpClient, new NullLogger());

        $token = $tokenManager->getToken();

        $this->assertInstanceOf(Token::class, $token);
        $this->assertEquals('test-token', $token->getAccessToken());
    }

    /**
     * @runInSeparateProcess
     */
    public function testReuseToken()
    {
        // Use mock to avoid actual JWT signing
        $this->mockJwtEncoding();

        $config = Config::fromArray($this->getConfig());

        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')
            ->once()
            ->andReturn(json_encode([
                'access_token' => 'test-token',
                'expires_in'   => 3600,
            ]));

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')
            ->once()
            ->andReturn(200);
        $response->shouldReceive('getBody')
            ->once()
            ->andReturn($stream);

        $httpClient = Mockery::mock(HttpClientInterface::class);
        $httpClient->shouldReceive('request')
            ->once()
            ->with('POST', $config->getAuthUrl(), Mockery::type('array'))
            ->andReturn($response);

        $tokenManager = new TokenManager($config, $httpClient, new NullLogger());

        $token1 = $tokenManager->getToken();
        $token2 = $tokenManager->getToken();

        $this->assertSame($token1, $token2);
    }

    /**
     * Mock the JWT::encode function to avoid actual signing
     */
    protected function mockJwtEncoding()
    {
        // Create a mock for JWT class
        $mock = Mockery::mock('alias:Firebase\JWT\JWT');
        $mock->shouldReceive('encode')
            ->withAnyArgs()
            ->andReturn('mocked.jwt.token');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
