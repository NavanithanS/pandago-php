<?php
namespace Nava\Pandago\Http;

use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use Nava\Pandago\Contracts\HttpClientInterface;
use Psr\Http\Message\ResponseInterface;

class GuzzleHttpClient implements HttpClientInterface
{
    /**
     * @var GuzzleClientInterface
     */
    protected $client;

    /**
     * GuzzleHttpClient constructor.
     *
     * @param GuzzleClientInterface $client
     */
    public function __construct(GuzzleClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Send a request to the API.
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        return $this->client->request($method, $uri, $options);
    }
}
