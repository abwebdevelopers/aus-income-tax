<?php
namespace ABWeb\IncomeTax\Tests\TestCase;

use ABWeb\IncomeTax\IncomeTax;
use ABWeb\IncomeTax\Source\ATOExcelSource;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

class WeeklyTest extends \PHPUnit_Framework_TestCase
{
    // Values taken from https://www.ato.gov.au/uploadedFiles/Content/MEI/downloads/Schedule-1-Calculating-amounts-to-be-withheld-2015-16.pdf
    protected $data = [
        '1' => [ // Scale 1 - No tax-free threshold
            44 => 8,
            45 => 9,
            116 => 25,
            117 => 25,
            249 => 56,
            250 => 56,
            354 => 80,
            355 => 81,
            360 => 82,
            361 => 82,
            394 => 94,
            395 => 94,
            492 => 128,
            493 => 128,
            659 => 186,
            660 => 186,
            710 => 204,
            711 => 204,
            825 => 244,
            826 => 244,
            931 => 280,
            932 => 281,
            1187 => 369,
            1188 => 369,
            1281 => 405,
            1282 => 406,
            1537 => 505,
            1538 => 506,
            1844 => 625,
            1845 => 625,
            2119 => 732,
            2120 => 733,
            2490 => 877,
            2491 => 877,
            2652 => 940,
            2653 => 940,
            2736 => 973,
            2737 => 973,
            2898 => 1036,
            2899 => 1036,
            2913 => 1042,
            2914 => 1042,
            3110 => 1119,
            3111 => 1119,
            3460 => 1290,
            3461 => 1291
        ],
        '2' => [ // Scale 2 - With tax-free threshold
            44 => 0,
            45 => 0,
            116 => 0,
            117 => 0,
            249 => 0,
            250 => 0,
            354 => 0,
            355 => 0,
            360 => 1,
            361 => 1,
            394 => 8,
            395 => 8,
            492 => 36,
            493 => 36,
            659 => 71,
            660 => 71,
            710 => 82,
            711 => 82,
            825 => 122,
            826 => 122,
            931 => 159,
            932 => 159,
            1187 => 248,
            1188 => 248,
            1281 => 280,
            1282 => 281,
            1537 => 369,
            1538 => 369,
            1844 => 488,
            1845 => 489,
            2119 => 596,
            2120 => 596,
            2490 => 740,
            2491 => 741,
            2652 => 803,
            2653 => 804,
            2736 => 836,
            2737 => 837,
            2898 => 899,
            2899 => 900,
            2913 => 905,
            2914 => 906,
            3110 => 982,
            3111 => 982,
            3460 => 1119,
            3461 => 1119
        ],
        '3' => [ // Scale 3 - Foreign residents
            44 => 14,
            45 => 15,
            116 => 38,
            117 => 38,
            249 => 81,
            250 => 81,
            354 => 115,
            355 => 115,
            360 => 117,
            361 => 117,
            394 => 128,
            395 => 128,
            492 => 160,
            493 => 160,
            659 => 214,
            660 => 214,
            710 => 231,
            711 => 231,
            825 => 268,
            826 => 268,
            931 => 303,
            932 => 303,
            1187 => 386,
            1188 => 386,
            1281 => 416,
            1282 => 417,
            1537 => 500,
            1538 => 500,
            1844 => 613,
            1845 => 614,
            2119 => 715,
            2120 => 716,
            2490 => 852,
            2491 => 853,
            2652 => 912,
            2653 => 913,
            2736 => 943,
            2737 => 944,
            2898 => 1003,
            2899 => 1004,
            2913 => 1009,
            2914 => 1009,
            3110 => 1082,
            3111 => 1082,
            3460 => 1211,
            3461 => 1212
        ],
        '4' => [ // Scale 4 - No TFN provided
            44 => 21,
            249 => 122,
            492 => 241,
            825 => 404,
            1538 => 753,
            2652 => 1299,
            3460 => 1695
        ],
        '5' => [ // Scale 5 - Full Medicare Levy
            44 => 0,
            45 => 0,
            116 => 0,
            117 => 0,
            249 => 0,
            250 => 0,
            354 => 0,
            355 => 0,
            360 => 1,
            361 => 1,
            394 => 8,
            395 => 8,
            492 => 26,
            493 => 26,
            659 => 58,
            660 => 58,
            710 => 68,
            711 => 68,
            825 => 105,
            826 => 106,
            931 => 140,
            932 => 140,
            1187 => 224,
            1188 => 224,
            1281 => 255,
            1282 => 255,
            1537 => 338,
            1538 => 338,
            1844 => 451,
            1845 => 452,
            2119 => 553,
            2120 => 554,
            2490 => 690,
            2491 => 691,
            2652 => 750,
            2653 => 751,
            2736 => 781,
            2737 => 782,
            2898 => 841,
            2899 => 842,
            2913 => 847,
            2914 => 847,
            3110 => 920,
            3111 => 920,
            3460 => 1049,
            3461 => 1050
        ],
        '6' => [ // Scale 6 - Full Medicare Levy
            44 => 0,
            45 => 0,
            116 => 0,
            117 => 0,
            249 => 0,
            250 => 0,
            354 => 0,
            355 => 0,
            360 => 1,
            361 => 1,
            394 => 8,
            395 => 8,
            492 => 26,
            493 => 26,
            659 => 58,
            660 => 58,
            710 => 70,
            711 => 70,
            825 => 113,
            826 => 114,
            931 => 149,
            932 => 150,
            1187 => 236,
            1188 => 236,
            1281 => 267,
            1282 => 268,
            1537 => 353,
            1538 => 354,
            1844 => 470,
            1845 => 470,
            2119 => 574,
            2120 => 575,
            2490 => 715,
            2491 => 716,
            2652 => 777,
            2653 => 777,
            2736 => 809,
            2737 => 809,
            2898 => 870,
            2899 => 871,
            2913 => 876,
            2914 => 876,
            3110 => 951,
            3111 => 951,
            3460 => 1084,
            3461 => 1084
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
            $tax = $this->IncomeTax->calculateTax($earnings, 'weekly', '2015-06-02', 'standard', 1);
            $this->assertEquals($expectedTax, $tax, 'Scale 1 - Weekly Earnings: ' . $earnings);
        }
    }

    public function testScaleTwo()
    {
        foreach ($this->data[2] as $earnings => $expectedTax) {
            $tax = $this->IncomeTax->calculateTax($earnings, 'weekly', '2015-06-02', 'standard', 2);
            $this->assertEquals($expectedTax, $tax, 'Scale 2 - Weekly Earnings: ' . $earnings);
        }
    }

    public function testScaleThree()
    {
        foreach ($this->data[3] as $earnings => $expectedTax) {
            $tax = $this->IncomeTax->calculateTax($earnings, 'weekly', '2015-06-02', 'standard', 3);
            $this->assertEquals($expectedTax, $tax, 'Scale 3 - Weekly Earnings: ' . $earnings);
        }
    }

    public function testScaleFour()
    {
        foreach ($this->data[4] as $earnings => $expectedTax) {
            $tax = $this->IncomeTax->calculateTax($earnings, 'weekly', '2015-06-02', 'standard', '4 resident');
            $this->assertEquals($expectedTax, $tax, 'Scale 4 - Weekly Earnings: ' . $earnings);
        }
    }

    public function testScaleFive()
    {
        foreach ($this->data[5] as $earnings => $expectedTax) {
            $tax = $this->IncomeTax->calculateTax($earnings, 'weekly', '2015-06-02', 'standard', 5);
            $this->assertEquals($expectedTax, $tax, 'Scale 5 - Weekly Earnings: ' . $earnings);
        }
    }

    public function testScaleSix()
    {
        foreach ($this->data[6] as $earnings => $expectedTax) {
            $tax = $this->IncomeTax->calculateTax($earnings, 'weekly', '2015-06-02', 'standard', 6);
            $this->assertEquals($expectedTax, $tax, 'Scale 6 - Weekly Earnings: ' . $earnings);
        }
    }
}
