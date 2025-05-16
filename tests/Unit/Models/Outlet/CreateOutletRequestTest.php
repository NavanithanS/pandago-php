<?php
namespace Nava\Pandago\Tests\Unit\Models\Outlet;

use Nava\Pandago\Exceptions\ValidationException;
use Nava\Pandago\Models\Outlet\CreateOutletRequest;
use Nava\Pandago\Tests\Helpers\TestAddresses;
use Nava\Pandago\Tests\TestCase;

class CreateOutletRequestTest extends TestCase
{
    /**
     * Test creating a valid outlet request.
     *
     * @return void
     */
    public function testConstructWithValidParameters()
    {
        $request = new CreateOutletRequest(
            TestAddresses::OUTLET_NAME,
            TestAddresses::OUTLET_ADDRESS,
            TestAddresses::OUTLET_LATITUDE,
            TestAddresses::OUTLET_LONGITUDE,
            TestAddresses::OUTLET_CITY,
            TestAddresses::OUTLET_PHONE,
            TestAddresses::OUTLET_CURRENCY,
            TestAddresses::OUTLET_LOCALE,
            TestAddresses::OUTLET_DESCRIPTION
        );

        $this->assertEquals(TestAddresses::OUTLET_NAME, $request->toArray()['name']);
        $this->assertEquals(TestAddresses::OUTLET_ADDRESS, $request->toArray()['address']);
        $this->assertEquals(TestAddresses::OUTLET_LATITUDE, $request->toArray()['latitude']);
        $this->assertEquals(TestAddresses::OUTLET_LONGITUDE, $request->toArray()['longitude']);
        $this->assertEquals(TestAddresses::OUTLET_CITY, $request->toArray()['city']);
        $this->assertEquals(TestAddresses::OUTLET_PHONE, $request->toArray()['phone_number']);
        $this->assertEquals(TestAddresses::OUTLET_CURRENCY, $request->toArray()['currency']);
        $this->assertEquals(TestAddresses::OUTLET_LOCALE, $request->toArray()['locale']);
        $this->assertEquals(TestAddresses::OUTLET_DESCRIPTION, $request->toArray()['description']);
    }

    /**
     * Test creating an outlet request with invalid parameters.
     *
     * @return void
     */
    public function testConstructWithInvalidParameters()
    {
        $this->expectException(ValidationException::class);

        // Empty name (should fail validation)
        new CreateOutletRequest(
            '', // empty name
            '1st Floor, No 8, Jalan Laguna 1',
            5.3731476,
            100.4068053,
            'Prai',
            '+601110550716',
            'MYR',
            'en-MY'
        );
    }

    /**
     * Test creating an outlet request from an array.
     *
     * @return void
     */
    public function testFromArray()
    {
        $data = [
            'name'               => 'Trilobyte',
            'address'            => '1st Floor, No 8, Jalan Laguna 1',
            'latitude'           => 5.3731476,
            'longitude'          => 100.4068053,
            'city'               => 'Prai',
            'phone_number'       => '+601110550716',
            'currency'           => 'MYR',
            'locale'             => 'en-MY',
            'description'        => 'My store description',
            'street'             => 'Jalan Laguna 1',
            'street_number'      => '8',
            'building'           => '1st Floor',
            'district'           => 'Seberang Perai',
            'postal_code'        => '13600',
            'rider_instructions' => 'Use the left side door',
            'halal'              => true,
            'add_user'           => ['chalit@example.com', 'guangyou@example.com'],
        ];

        $request = CreateOutletRequest::fromArray($data);

        $array = $request->toArray();
        $this->assertEquals('Trilobyte', $array['name']);
        $this->assertEquals('1st Floor, No 8, Jalan Laguna 1', $array['address']);
        $this->assertEquals(5.3731476, $array['latitude']);
        $this->assertEquals(100.4068053, $array['longitude']);
        $this->assertEquals('Prai', $array['city']);
        $this->assertEquals('+601110550716', $array['phone_number']);
        $this->assertEquals('MYR', $array['currency']);
        $this->assertEquals('en-MY', $array['locale']);
        $this->assertEquals('My store description', $array['description']);
        $this->assertEquals('Jalan Laguna 1', $array['street']);
        $this->assertEquals('8', $array['street_number']);
        $this->assertEquals('1st Floor', $array['building']);
        $this->assertEquals('Seberang Perai', $array['district']);
        $this->assertEquals('13600', $array['postal_code']);
        $this->assertEquals('Use the left side door', $array['rider_instructions']);
        $this->assertTrue($array['halal']);
        $this->assertEquals(['chalit@example.com', 'guangyou@example.com'], $array['add_user']);
    }

    /**
     * Test setting optional parameters.
     *
     * @return void
     */
    public function testSetters()
    {
        $request = new CreateOutletRequest(
            TestAddresses::OUTLET_NAME,
            TestAddresses::OUTLET_ADDRESS,
            TestAddresses::OUTLET_LATITUDE,
            TestAddresses::OUTLET_LONGITUDE,
            TestAddresses::OUTLET_CITY,
            TestAddresses::OUTLET_PHONE,
            TestAddresses::OUTLET_CURRENCY,
            TestAddresses::OUTLET_LOCALE,
            TestAddresses::OUTLET_DESCRIPTION
        );

        $request->setStreet('Jalan Laguna 1');
        $request->setStreetNumber('8');
        $request->setBuilding('1st Floor');
        $request->setDistrict('Seberang Perai');
        $request->setPostalCode('13600');
        $request->setRiderInstructions('Use the left side door');
        $request->setHalal(true);
        $request->setAddUsers(['chalit@example.com', 'guangyou@example.com']);
        $request->setDeleteUsers(['olduser@example.com']);

        $array = $request->toArray();
        $this->assertEquals('Jalan Laguna 1', $array['street']);
        $this->assertEquals('8', $array['street_number']);
        $this->assertEquals('1st Floor', $array['building']);
        $this->assertEquals('Seberang Perai', $array['district']);
        $this->assertEquals('13600', $array['postal_code']);
        $this->assertEquals('Use the left side door', $array['rider_instructions']);
        $this->assertTrue($array['halal']);
        $this->assertEquals(['chalit@example.com', 'guangyou@example.com'], $array['add_user']);
        $this->assertEquals(['olduser@example.com'], $array['delete_user']);
    }

    /**
     * Test validation for setter methods.
     *
     * @return void
     */
    public function testSetterValidation()
    {
        $request = new CreateOutletRequest(
            TestAddresses::OUTLET_NAME,
            TestAddresses::OUTLET_ADDRESS,
            TestAddresses::OUTLET_LATITUDE,
            TestAddresses::OUTLET_LONGITUDE,
            TestAddresses::OUTLET_CITY,
            TestAddresses::OUTLET_PHONE,
            TestAddresses::OUTLET_CURRENCY,
            TestAddresses::OUTLET_LOCALE,
            TestAddresses::OUTLET_DESCRIPTION
        );

        $this->expectException(ValidationException::class);

        // Generate a string that exceeds the max length (300 characters)
        $longString = str_repeat('a', 301);
        $request->setStreet($longString);
    }

    /**
     * Test toArray method.
     *
     * @return void
     */
    public function testToArray()
    {
        $request = new CreateOutletRequest(
            TestAddresses::OUTLET_NAME,
            TestAddresses::OUTLET_ADDRESS,
            TestAddresses::OUTLET_LATITUDE,
            TestAddresses::OUTLET_LONGITUDE,
            TestAddresses::OUTLET_CITY,
            TestAddresses::OUTLET_PHONE,
            TestAddresses::OUTLET_CURRENCY,
            TestAddresses::OUTLET_LOCALE,
            TestAddresses::OUTLET_DESCRIPTION
        );

        $array = $request->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('address', $array);
        $this->assertArrayHasKey('latitude', $array);
        $this->assertArrayHasKey('longitude', $array);
        $this->assertArrayHasKey('city', $array);
        $this->assertArrayHasKey('phone_number', $array);
        $this->assertArrayHasKey('currency', $array);
        $this->assertArrayHasKey('locale', $array);
        $this->assertArrayHasKey('description', $array);
    }
}
