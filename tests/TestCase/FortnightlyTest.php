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
}
