<?php
namespace Nava\Pandago;

use Nava\Pandago\Exceptions\PandagoException;
use Psr\Log\LoggerInterface;

class Client
{
    /**
     * Create a new PandagoClient instance.
     *
     * @param string $clientId
     * @param string $keyId
     * @param string $scope
     * @param string $privateKey
     * @param string $country
     * @param string $environment
     * @param int $timeout
     * @param LoggerInterface|null $logger
     * @return PandagoClient
     * @throws PandagoException
     */
    public static function make(
        string $clientId,
        string $keyId,
        string $scope,
        string $privateKey,
        string $country = 'my',
        string $environment = 'sandbox',
        int $timeout = 30,
        ?LoggerInterface $logger = null
    ): PandagoClient {
        $config = new Config(
            $clientId,
            $keyId,
            $scope,
            $privateKey,
            $country,
            $environment,
            $timeout
        );

        return new PandagoClient($config, null, $logger);
    }

    /**
     * Create a new PandagoClient instance from an array of config options.
     *
     * @param array $config
     * @param LoggerInterface|null $logger
     * @return PandagoClient
     * @throws PandagoException
     */
    public static function fromArray(array $config, ?LoggerInterface $logger = null): PandagoClient
    {
        return new PandagoClient(Config::fromArray($config), null, $logger);
    }
}
