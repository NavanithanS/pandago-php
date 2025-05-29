<?php
namespace Nava\Pandago;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Nava\Pandago\Models\Contact;
use Nava\Pandago\Models\Location;
use Nava\Pandago\Models\Outlet\Outlet;

class PandagoAddress
{

    public static function prepareData($data)
    {
        $output = [];
        if ($data) {
            $unit = isset($data['deliveryaddress']['unit']) ? $data['deliveryaddress']['unit'] . " " : null;
            $address = isset($data['deliveryaddress']['address']) ? $data['deliveryaddress']['address'] : null;
            $address2 = isset($data['deliveryaddress']['address2']) ? " " . $data['deliveryaddress']['address2'] : null;
            $output['address'] = $unit . "" . $address . "" . $address2;
            $output['postcode'] = isset($data['deliveryaddress']['postcode']) ? $data['deliveryaddress']['postcode'] : null;
            $output['name'] = isset($data['deliveryaddress']['name']) ? $data['deliveryaddress']['name'] : null;
            $output['phone'] = isset($data['deliveryaddress']['phone']) ? $data['deliveryaddress']['phone'] : null;
            $output['store_id'] = 'outlet-test-6826edbbce1d0';//isset($data['deliveryaddress']['_id']) ? $data['deliveryaddress']['_id'] : null;
            $output['lat'] = isset($data['deliveryaddress']['lat']) ? (string) $data['deliveryaddress']['lat'] : null;
            $output['lng'] = isset($data['deliveryaddress']['lng']) ? (string) $data['deliveryaddress']['lng'] : null;
            if (!$data['date']) {
                $output['dateTime'] = Carbon::now(); 
            } else {
                $output['dateTime'] = Carbon::parse($data['date'] . ' ' . $data['time']);
            }
            $output['subtotal'] = isset($data['subtotal']) ? $data['subtotal'] : null;
        }

        return $output;
    }

    public static function prepareCreateOrderData($data)
    {
        $output = [];
        if ($data) {
            $unit = isset($data['content']['address']['unit']) ? $data['content']['address']['unit'] . " " : null;
            $address = isset($data['content']['address']['address']) ? $data['content']['address']['address'] : null;
            $address2 = isset($data['content']['address']['address2']) ? " " . $data['content']['address']['address2'] : null;
            $output['address'] = $unit . "" . $address . "" . $address2;
            $output['postcode'] = isset($data['content']['address']['postcode']) ? $data['content']['address']['postcode'] : null;
            $output['name'] = isset($data['content']['address']['name']) ? $data['content']['address']['name'] : null;
            $output['phone'] = isset($data['content']['address']['phone']) ? $data['content']['address']['phone'] : null;
            $output['store_id'] = isset($data['content']['shipping_store']) ? $data['content']['shipping_store'] : null;
            $output['lat'] = isset($data['content']['address']['lat']) ? $data['content']['address']['lat'] : null;
            $output['lng'] = isset($data['content']['address']['lng']) ? $data['content']['address']['lng'] : null;
            $output['dateTime'] = Carbon::parse($data['content']['shipping_date'] . ' ' . $data['content']['shipping_time']);
            $output['subtotal'] = isset($data['subtotal']) ? $data['subtotal'] : null;
            $output['refno'] = isset($data['refno']) ? $data['refno'] : null;
        }

        return $output;
    }


    /**
     * Get the outlet location object.
     *
     * @return Location
     */
    public static function getOutletLocation($store = null): Location
    {
        return new Location(
            $store['address'],
            $store['lat'],
            $store['lng'],
            // $store->address,
            // $store->lat,
            // $store->lng,
        );
    }

    /**
     * Get the outlet contact object.
     *
     * @param string|null $notes Optional notes for delivery instructions
     * @return Contact
     */
    public static function getOutletContact($store = null, ?string $notes = null): Contact
    {
        return new Contact(
            $store['title'],
            $store['contact'],
            self::getOutletLocation($store),
            $notes ?? 'Use the food hall entrance at B2'
        );
    }

    /**
     * Get the customer location object.
     *
     * @return Location
     */
    public static function getCustomerLocation(array $data = []): Location
    {
        return new Location(
            $data['address'],
            $data['lat'],
            $data['lng'],
        );
    }

    /**
     * Get the customer contact object.
     *
     * @param string|null $notes Optional notes for delivery instructions
     * @return Contact
     */
    public static function getCustomerContact(array $data = [], ?string $notes = null): Contact
    {
        return new Contact(
            $data['name'],
            $data['phone'],
            self::getCustomerLocation($data),
            $notes
        );
    }

    /**
     * Get an out-of-range location for negative testing.
     *
     * @return Location
     */
    public static function getOutOfRangeLocation(): Location
    {
        return new Location(
            self::OUT_OF_RANGE_ADDRESS,
            self::OUT_OF_RANGE_LATITUDE,
            self::OUT_OF_RANGE_LONGITUDE
        );
    }

    /**
     * Get an out-of-range contact for negative testing.
     *
     * @return Contact
     */
    public static function getOutOfRangeContact(): Contact
    {
        return new Contact(
            'Out of Range Customer',
            '+6587654321',
            self::getOutOfRangeLocation()
        );
    }

    /**
     * Create a standard outlet creation request.
     *
     * @param string|null $description Optional description
     * @return Outlet
     */
    public static function createOutletRequest($store = null, ?string $description = null): Outlet
    {
        return new Outlet([
            'name' => $store['title'] ?? '',
            'address' => $store['address'] ?? '',
            'latitude' => $store['lat'] ?? '',
            'longitude' => $store['lng'] ?? '',
            'city' => $store['city'] ?? '',
            'phone_number' => $store['contact'] ?? '',
            'currency' => 'SGD' ?? '',
            'locale' => 'en-SG' ?? '',
            'description' => 'Create outlet description',
        ]);
    }

    /**
     * Get distance between outlet and customer (approximate).
     *
     * @return float Distance in kilometers
     */
    public static function getApproximateDistance(): float
    {
                     // Calculated using Haversine formula
        return 0.48; // Approximately 480 meters
    }

    /**
     * Generate a unique client order ID for testing.
     *
     * @param string $prefix
     * @return string
     */
    public static function generateClientOrderId(string $prefix = 'test'): string
    {
        return $prefix . '-' . uniqid();
    }

    /**
     * Generate a unique client vendor ID for testing.
     *
     * @param string $prefix
     * @return string
     */
    public static function generateClientVendorId(string $prefix = 'outlet'): string
    {
        return $prefix . '-' . uniqid();
    }

    /**
     * Get standard delivery notes for testing.
     *
     * @return array
     */
    public static function getDeliveryNotes(): array
    {
        return [
            'sender'    => 'Use the food hall entrance at B2',
            'recipient' => 'Call upon arrival',
            'special'   => 'Handle with care - fragile items',
        ];
    }

    /**
     * Get test amounts for different scenarios.
     *
     * @return array
     */
    public static function getTestAmounts(): array
    {
        return [
            'small'            => 15.50,
            'medium'           => 35.75,
            'large'            => 89.99,
            'cash_on_delivery' => 45.00,
        ];
    }
}
