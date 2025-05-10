<?php
namespace Nava\Pandago\Resources;

use Nava\Pandago\Contracts\ClientInterface;
use Nava\Pandago\Models\Outlet\CreateOutletRequest;
use Nava\Pandago\Models\Outlet\Outlet;

class OutletResource
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * Constructor.
     *
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Create or update an outlet.
     *
     * @param string $clientVendorId Client vendor ID
     * @param CreateOutletRequest|Outlet $outlet Outlet data
     * @return Outlet
     */
    public function createOrUpdate(string $clientVendorId, $outlet)
    {
        // Check the type of $outlet and handle accordingly
        if (! ($outlet instanceof Outlet) && ! ($outlet instanceof CreateOutletRequest)) {
            throw new \InvalidArgumentException(
                'Outlet parameter must be an instance of Outlet or CreateOutletRequest'
            );
        }

        // Convert to array for the request
        $data = $outlet->toArray();

        $response = $this->client->request('PUT', "/outlets/{$clientVendorId}", [
            'json' => $data,
        ]);

        return Outlet::fromArray($response);
    }

    /**
     * Get an outlet.
     *
     * @param string $clientVendorId Client vendor ID
     * @return Outlet
     */
    public function get(string $clientVendorId)
    {
        $response = $this->client->request('GET', "/outlets/{$clientVendorId}");

        return Outlet::fromArray($response);
    }

    /**
     * Get all outlets.
     *
     * @return array
     */
    public function getAll()
    {
        $response = $this->client->request('GET', '/outletList');

        $outlets = [];
        foreach ($response as $outletData) {
            $outlets[] = Outlet::fromArray($outletData);
        }

        return $outlets;
    }
}
