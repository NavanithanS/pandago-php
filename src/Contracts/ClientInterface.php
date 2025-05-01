<?php
namespace Nava\Pandago\Contracts;

use Nava\Pandago\Config;
use Nava\Pandago\Exceptions\PandagoException;
use Nava\Pandago\Exceptions\RequestException;
use Nava\Pandago\Resources\OrderResource;
use Nava\Pandago\Resources\OutletResource;

interface ClientInterface
{
    /**
     * Get the order resource.
     *
     * @return OrderResource
     */
    public function orders(): OrderResource;

    /**
     * Get the outlet resource.
     *
     * @return OutletResource
     */
    public function outlets(): OutletResource;

    /**
     * Make a request to the API.
     *
     * @param string $method
     * @param string $path
     * @param array $options
     * @return array
     * @throws PandagoException
     * @throws RequestException
     */
    public function request(string $method, string $path, array $options = []): array;

    /**
     * Get the configuration.
     *
     * @return Config
     */
    public function getConfig(): Config;
}
