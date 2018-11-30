<?php

/**
 * For Dutch consignments the street should be divided into name, number and addition. This code tests whether the
 * street is split properly.
 *
 * If you want to add improvements, please create a fork in our GitHub:
 * https://github.com/myparcelnl
 *
 * @author      Reindert Vetter <reindert@myparcel.nl>
 * @copyright   2010-2017 MyParcel
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US  CC BY-NC-ND 3.0 NL
 * @link        https://github.com/myparcelnl/sdk
 * @since       File available since Release v0.1.0
 */

namespace MyParcelNL\Sdk\src\tests\CreateConsignments\SplitStreetTest;
use MyParcelNL\Sdk\src\Model\Repository\MyParcelConsignmentRepository;


/**
 * Class SplitStreetTest
 * @package MyParcelNL\Sdk\src\tests\SplitStreetTest
 */
class SplitStreetTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @covers       \MyParcelNL\Sdk\src\Model\Repository\MyParcelConsignmentRepository::setFullStreet
     * @dataProvider additionProvider()
     * @param $fullStreetTest
     * @param $fullStreet
     * @param $street
     * @param $number
     * @param $numberSuffix
     * @throws \Exception
     */
    public function testSplitStreet($fullStreetTest, $fullStreet, $street, $number, $numberSuffix)
    {
        $consignment = (new MyParcelConsignmentRepository())
            ->setCountry('NL')
            ->setFullStreet($fullStreetTest);

        $this->assertEquals($street,        $consignment->getStreet(),       'Street: ' . $fullStreetTest);
        $this->assertEquals($number,        $consignment->getNumber(),       'Number from: ' . $fullStreetTest);
        $this->assertEquals($numberSuffix,  $consignment->getNumberSuffix(), 'Number suffix from: ' . $fullStreetTest);
        $this->assertEquals($fullStreet,    $consignment->getFullStreet(),   'Full street: ' . $fullStreetTest);
    }

    /**
     * Data for the test
     *
     * @return array
     */
    public function additionProvider()
    {
        return [
            'Plein 1945 27' => [
                'full_street_test' => 'Plein 1945 27',
                'full_street' => 'Plein 1945 27',
                'street' => 'Plein 1945',
                'number' => 27,
                'number_suffix' => '',
            ],
            'Plein 1940-45 3b' => [
                'full_street_test' => 'Plein 1940-45 3b',
                'full_street' => 'Plein 1940-45 3 b',
                'street' => 'Plein 1940-45',
                'number' => 3,
                'number_suffix' => 'b',
            ],
            '300 laan 3' => [
                'full_street_test' => '300 laan 3',
                'full_street' => '300 laan 3',
                'street' => '300 laan',
                'number' => 3,
                'number_suffix' => '',
            ],
            'A.B.C. street 12' => [
                'full_street_test' => 'A.B.C. street 12',
                'full_street' => 'A.B.C. street 12',
                'street' => 'A.B.C. street',
                'number' => 12,
                'number_suffix' => '',
            ],
            'street street 269-133' => [
                'full_street_test' => 'street street 269-133',
                'full_street' => 'street street 269 133',
                'street' => 'street street',
                'number' => 269,
                'number_suffix' => '133',
            ],
            'Abeelstreet H10' => [
                'full_street_test' => 'Abeelstreet H10',
                'full_street' => 'Abeelstreet H 10',
                'street' => 'Abeelstreet H',
                'number' => 10,
                'number_suffix' => '',
            ],
            'street street 269-1001' => [
                'full_street_test' => 'street street 269-1001',
                'full_street' => 'street street 269 1001',
                'street' => 'street street',
                'number' => 269,
                'number_suffix' => '1001',
            ],
            'Meijhorst 50e 26' => [
                'full_street_test' => 'Meijhorst 50e 26',
                'full_street' => 'Meijhorst 50e 26',
                'street' => 'Meijhorst 50e',
                'number' => 26,
                'number_suffix' => '',
            ],
            'street street 12 ZW' => [
                'full_street_test' => 'street street 12 ZW',
                'full_street' => 'street street 12 ZW',
                'street' => 'street street',
                'number' => 12,
                'number_suffix' => 'ZW',
            ],
            'street 12' => [
                'full_street_test' => 'street 12',
                'full_street' => 'street 12',
                'street' => 'street',
                'number' => 12,
                'number_suffix' => '',
            ],
            'Biltstreet 113 A BS' => [
                'full_street_test' => 'Biltstreet 113 A BS',
                'full_street' => 'Biltstreet 113 A BS',
                'street' => 'Biltstreet',
                'number' => 113,
                'number_suffix' => 'A BS',
            ],
            'Zonegge 23 12' => [
                'full_street_test' => 'Zonegge 23 12',
                'full_street' => 'Zonegge 23 12',
                'street' => 'Zonegge 23',
                'number' => 12,
                'number_suffix' => '',
            ],
            'Markerkant 10 142' => [
                'full_street_test' => 'Markerkant 10 142',
                'full_street' => 'Markerkant 10 142',
                'street' => 'Markerkant',
                'number' => 10,
                'number_suffix' => '142',
            ],
            'Markerkant 10 11e' => [
                'full_street_test' => 'Markerkant 10 11e',
                'full_street' => 'Markerkant 10 11e',
                'street' => 'Markerkant',
                'number' => 10,
                'number_suffix' => '11e',
            ],
            'Sir Winston Churchillln 283 F008' => [
                'full_street_test' => 'Sir Winston Churchillln 283 F008',
                'full_street' => 'Sir Winston Churchillln 283 F008',
                'street' => 'Sir Winston Churchillln',
                'number' => 283,
                'number_suffix' => 'F008',
            ],
            'Woning Sir Winston Churchillln 283-9' => [
                'full_street_test' => 'Woning Sir Winston Churchillln 283-9',
                'full_street' => 'Woning Sir Winston Churchillln 283 9',
                'street' => 'Woning Sir Winston Churchillln',
                'number' => 283,
                'number_suffix' => '9',
            ],
            'Insulindestreet 69 B03' => [
                'full_street_test' => 'Insulindestreet 69 B03',
                'full_street' => 'Insulindestreet 69 B03',
                'street' => 'Insulindestreet',
                'number' => 69,
                'number_suffix' => 'B03',
            ],
            'Scheepvaartlaan 34/302' => [
                'full_street_test' => 'Scheepvaartlaan 34/302',
                'full_street' => 'Scheepvaartlaan 34 302',
                'street' => 'Scheepvaartlaan',
                'number' => 34,
                'number_suffix' => '302',
            ],
            'oan e dijk 48' => [
                'full_street_test' => 'oan e dijk 48',
                'full_street' => 'oan e dijk 48',
                'street' => 'oan e dijk',
                'number' => 48,
                'number_suffix' => '',
            ],
            'Vlinderveen137' => [
                'full_street_test' => 'Vlinderveen137',
                'full_street' => 'Vlinderveen 137',
                'street' => 'Vlinderveen',
                'number' => 137,
                'number_suffix' => '',
            ],
            'street 39-1hg' => [
                'full_street_test' => 'street 39-1hg',
                'full_street' => 'street 39- 1 hg',
                'street' => 'street 39-',
                'number' => 1,
                'number_suffix' => 'hg',
            ],
            'Nicolaas Ruyschstraat 8 02L' => [
                'full_street_test' => 'Nicolaas Ruyschstraat 8 02L',
                'full_street' => 'Nicolaas Ruyschstraat 8 02L',
                'street' => 'Nicolaas Ruyschstraat',
                'number' => 8,
                'number_suffix' => '02L',
            ],
            'Landsdijk 49 -A' => [
                'full_street_test' => 'Landsdijk 49 -A',
                'full_street' => 'Landsdijk 49 A',
                'street' => 'Landsdijk',
                'number' => 49,
                'number_suffix' => 'A',
            ],
        ];
    }
}