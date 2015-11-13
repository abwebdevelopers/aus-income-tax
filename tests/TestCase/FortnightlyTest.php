<?php
namespace ABWeb\IncomeTax\Tests\TestCase;

use ABWeb\IncomeTax\IncomeTax;
use ABWeb\IncomeTax\Source\ATOExcelSource;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

class FortnightlyTest extends \PHPUnit_Framework_TestCase
{
    // Values taken from https://www.ato.gov.au/uploadedFiles/Content/MEI/downloads/Schedule-1-Calculating-amounts-to-be-withheld-2015-16.pdf
    protected $data = [
        '1' => [ // Scale 1 - No tax-free threshold
            88 => 16,
            90 => 18,
            232 => 50,
            234 => 50,
            498 => 112,
            500 => 112,
            708 => 160,
            710 => 162,
            720 => 164,
            722 => 164,
            788 => 188,
            790 => 188,
            984 => 256,
            986 => 256,
            1318 => 372,
            1320 => 372,
            1420 => 408,
            1422 => 408,
            1650 => 488,
            1652 => 488,
            1862 => 560,
            1864 => 562,
            2374 => 738,
            2376 => 738,
            2562 => 810,
            2564 => 812,
            3074 => 1010,
            3076 => 1012,
            3688 => 1250,
            3690 => 1250,
            4238 => 1464,
            4240 => 1466,
            4980 => 1754,
            4982 => 1754,
            5304 => 1880,
            5306 => 1880,
            5472 => 1946,
            5474 => 1946,
            5796 => 2072,
            5798 => 2072,
            5826 => 2084,
            5828 => 2084,
            6220 => 2238,
            6222 => 2238,
            6920 => 2580,
            6922 => 2582
        ],
        '2' => [ // Scale 2 - With tax-free threshold
            88 => 0,
            90 => 0,
            232 => 0,
            234 => 0,
            498 => 0,
            500 => 0,
            708 => 0,
            710 => 0,
            720 => 2,
            722 => 2,
            788 => 16,
            790 => 16,
            984 => 72,
            986 => 72,
            1318 => 142,
            1320 => 142,
            1420 => 164,
            1422 => 164,
            1650 => 244,
            1652 => 244,
            1862 => 318,
            1864 => 318,
            2374 => 496,
            2376 => 496,
            2562 => 560,
            2564 => 562,
            3074 => 738,
            3076 => 738,
            3688 => 976,
            3690 => 978,
            4238 => 1192,
            4240 => 1192,
            4980 => 1480,
            4982 => 1482,
            5304 => 1606,
            5306 => 1608,
            5472 => 1672,
            5474 => 1674,
            5796 => 1798,
            5798 => 1800,
            5826 => 1810,
            5828 => 1812,
            6220 => 1964,
            6222 => 1964,
            6920 => 2238,
            6922 => 2238
        ],
        '3' => [ // Scale 3 - Foreign resident
            88 => 28,
            90 => 30,
            232 => 76,
            234 => 76,
            498 => 162,
            500 => 162,
            708 => 230,
            710 => 230,
            720 => 234,
            722 => 234,
            788 => 256,
            790 => 256,
            984 => 320,
            986 => 320,
            1318 => 428,
            1320 => 428,
            1420 => 462,
            1422 => 462,
            1650 => 536,
            1652 => 536,
            1862 => 606,
            1864 => 606,
            2374 => 772,
            2376 => 772,
            2562 => 832,
            2564 => 834,
            3074 => 1000,
            3076 => 1000,
            3688 => 1226,
            3690 => 1228,
            4238 => 1430,
            4240 => 1432,
            4980 => 1704,
            4982 => 1706,
            5304 => 1824,
            5306 => 1826,
            5472 => 1886,
            5474 => 1888,
            5796 => 2006,
            5798 => 2008,
            5826 => 2018,
            5828 => 2018,
            6220 => 2164,
            6222 => 2164,
            6920 => 2422,
            6922 => 2424
        ]
    ];

    public function setUp()
    {
        $this->IncomeTax = new IncomeTax(new ATOExcelSource([
            'standardFile' => dirname(dirname(__DIR__)) . DS . 'ext' . DS . '2015' . DS . 'NAT_1004_0.xlsx',
        ]));
    }

    public function testScaleOne()
    {
        foreach ($this->data[1] as $earnings => $expectedTax) {
            $tax = $this->IncomeTax->calculateTax($earnings, 'fortnightly', '2015-06-02', 'standard', 1);
            $this->assertEquals($expectedTax, $tax, 'Scale 1 - Fortnightly Earnings: ' . $earnings);
        }
    }

    public function testScaleTwo()
    {
        foreach ($this->data[2] as $earnings => $expectedTax) {
            $tax = $this->IncomeTax->calculateTax($earnings, 'fortnightly', '2015-06-02', 'standard', 2);
            $this->assertEquals($expectedTax, $tax, 'Scale 2 - Fortnightly Earnings: ' . $earnings);
        }
    }

    public function testScaleThree()
    {
        foreach ($this->data[3] as $earnings => $expectedTax) {
            $tax = $this->IncomeTax->calculateTax($earnings, 'fortnightly', '2015-06-02', 'standard', 3);
            $this->assertEquals($expectedTax, $tax, 'Scale 3 - Fortnightly Earnings: ' . $earnings);
        }
    }
}
