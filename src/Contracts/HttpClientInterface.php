<?php
namespace Nava\Pandago\Contracts;

use Psr\Http\Message\ResponseInterface;

interface HttpClientInterface
{
    /**
     * Send a request to the API.
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return ResponseInterface
     */
    public function request(string $method, string $uri, array $options = []): ResponseInterface;
}
