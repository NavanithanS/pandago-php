<?php
namespace Nava\Pandago\Resources;

use Nava\Pandago\Contracts\ClientInterface;
use Nava\Pandago\Exceptions\PandagoException;
use Nava\Pandago\Exceptions\RequestException;
use Nava\Pandago\Models\Outlet\Outlet;

class OutletResource
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * OutletResource constructor.
     *
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Get an outlet by client vendor ID.
     *
     * @param string $clientVendorId
     * @return Outlet
     * @throws PandagoException
     * @throws RequestException
     */
    public function get(string $clientVendorId): Outlet
    {
        $response = $this->client->request('GET', "/outlets/{$clientVendorId}");

        return Outlet::fromArray($response);
    }

    /**
     * Create or update an outlet.
     *
     * @param string $clientVendorId
     * @param Outlet $outlet
     * @return Outlet
     * @throws PandagoException
     * @throws RequestException
     */
    public function createOrUpdate(string $clientVendorId, Outlet $outlet): Outlet
    {
        $response = $this->client->request('PUT', "/outlets/{$clientVendorId}", [
            'json' => $outlet->toArray(),
        ]);

        return Outlet::fromArray($response);
    }

    /**
     * Get all outlets for a brand vendor.
     *
     * @return array
     * @throws PandagoException
     * @throws RequestException
     */
    public function all(): array
    {
        $response = $this->client->request('GET', '/outletList');

        $outlets = [];
        foreach ($response as $outlet) {
            $outlets[] = Outlet::fromArray($outlet);
        }

        return $outlets;
    }
}
