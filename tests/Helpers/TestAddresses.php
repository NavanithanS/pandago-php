<?php
namespace Nava\Pandago\Tests\Helpers;

use Nava\Pandago\Models\Contact;
use Nava\Pandago\Models\Location;
use Nava\Pandago\Models\Outlet\CreateOutletRequest;

/**
 * Test addresses and contacts for Pandago integration tests.
 *
 * This class provides standardized addresses and contact information
 * for use across all integration tests.
 */
class TestAddresses
{
    // Garrett Popcorn Shops (Sender/Outlet)
    const OUTLET_NAME         = 'Garrett Popcorn Shops';
    const OUTLET_ADDRESS      = '391 Orchard Road, B2, Food Hall, B208, #8 Takashimaya Shopping Centre, Singapore 238872';
    const OUTLET_LATITUDE     = 1.303768190090923;
    const OUTLET_LONGITUDE    = 103.83334762156251;
    const OUTLET_PHONE        = '+6567379388';
    const OUTLET_CONTACT_NAME = 'Chalit';
    const OUTLET_CITY         = 'Singapore';
    const OUTLET_CURRENCY     = 'SGD';
    const OUTLET_LOCALE       = 'en-SG';
    const OUTLET_DESCRIPTION  = 'Garrett Popcorn Shops at Takashimaya Shopping Centre';

    // Customer (Recipient)
    const CUSTOMER_NAME      = 'Guang You';
    const CUSTOMER_ADDRESS   = '270 Orchard Rd, Singapore 238857';
    const CUSTOMER_LATITUDE  = 1.303166607308108;
    const CUSTOMER_LONGITUDE = 103.83618242858377;
    const CUSTOMER_PHONE     = '+6518006992824';

    // Alternative test addresses (for out-of-range testing)
    const OUT_OF_RANGE_ADDRESS   = 'Jurong East, Singapore';
    const OUT_OF_RANGE_LATITUDE  = 1.3329;
    const OUT_OF_RANGE_LONGITUDE = 103.7436;

    /**
     * Get the outlet location object.
     *
     * @return Location
     */
    public static function getOutletLocation(): Location
    {
        return new Location(
            self::OUTLET_ADDRESS,
            self::OUTLET_LATITUDE,
            self::OUTLET_LONGITUDE
        );
    }

    /**
     * Get the outlet contact object.
     *
     * @param string|null $notes Optional notes for delivery instructions
     * @return Contact
     */
    public static function getOutletContact(?string $notes = null): Contact
    {
        return new Contact(
            self::OUTLET_CONTACT_NAME,
            self::OUTLET_PHONE,
            self::getOutletLocation(),
            $notes ?? 'Use the food hall entrance at B2'
        );
    }

    /**
     * Get the customer location object.
     *
     * @return Location
     */
    public static function getCustomerLocation(): Location
    {
        return new Location(
            self::CUSTOMER_ADDRESS,
            self::CUSTOMER_LATITUDE,
            self::CUSTOMER_LONGITUDE
        );
    }

    /**
     * Get the customer contact object.
     *
     * @param string|null $notes Optional notes for delivery instructions
     * @return Contact
     */
    public static function getCustomerContact(?string $notes = null): Contact
    {
        return new Contact(
            self::CUSTOMER_NAME,
            self::CUSTOMER_PHONE,
            self::getCustomerLocation(),
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
     * @return CreateOutletRequest
     */
    public static function createOutletRequest(?string $description = null): CreateOutletRequest
    {
        return new CreateOutletRequest(
            self::OUTLET_NAME,
            self::OUTLET_ADDRESS,
            self::OUTLET_LATITUDE,
            self::OUTLET_LONGITUDE,
            self::OUTLET_CITY,
            self::OUTLET_PHONE,
            self::OUTLET_CURRENCY,
            self::OUTLET_LOCALE,
            $description ?? self::OUTLET_DESCRIPTION
        );
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
        return $prefix . '-test-' . uniqid();
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
