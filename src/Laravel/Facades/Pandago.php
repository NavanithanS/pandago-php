<?php
namespace Nava\Pandago\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Nava\Pandago\Resources\OrderResource;
use Nava\Pandago\Resources\OutletResource;

/**
 * @method static OrderResource orders()
 * @method static OutletResource outlets()
 *
 * @see \Nava\Pandago\PandagoClient
 */
class Pandago extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'pandago';
    }
}
