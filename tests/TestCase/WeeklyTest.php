<?php
namespace ABWebDevelopers\AusIncomeTax\Tests\TestCase;

use PHPUnit\Framework\TestCase;
use ABWebDevelopers\AusIncomeTax\IncomeTax;
use ABWebDevelopers\AusIncomeTax\Source\ATOExcelSource;

class WeeklyTest extends TestCase
{
    public function setUp(): void
    {
        if (!defined('DS')) {
            define('DS', $_ENV['DIRECTORY_SEPARATOR'] ?? '/');
        }

        $taxTableDir = implode(DS, [
            dirname(dirname(__DIR__)),
            'resources',
            'tax-tables',
            '2018-19',
        ]) . DS;

        $this->IncomeTax = new IncomeTax(new ATOExcelSource([
            'standardFile' => $taxTableDir . 'NAT_1004_2018.xlsx',
        ]));
    }

    /**
     * Test Scale 1 withheld amounts - Where Tax Free Threshold is not claimed
     *
     * Values taken from ATO's "Statement of formulas for calculating amounts to be withheld 2018-19" PDF
     * https://www.ato.gov.au/uploadedFiles/Content/MEI/downloads/Calculating-amounts-to-be-withheld-2018-19.pdf
     */
    public function testScaleOne()
    {
        $data = [
            71 => 13,
            72 => 14,
            116 => 24,
            117 => 24,
            249 => 55,
            250 => 56,
            354 => 80,
            355 => 80,
            360 => 81,
            361 => 82,
            421 => 102,
            422 => 103,
            527 => 139,
            528 => 140,
            710 => 203,
            711 => 203,
            712 => 204,
            713 => 204,
            890 => 266,
            891 => 266,
            931 => 280,
            932 => 280,
            1281 => 401,
            1282 => 401,
            1379 => 434,
            1380 => 435,
            1729 => 571,
            1730 => 571,
            1844 => 616,
            1845 => 616,
            2119 => 723,
            2120 => 723,
            2490 => 868,
            2491 => 868,
            2652 => 931,
            2653 => 931,
            2736 => 964,
            2737 => 964,
            2898 => 1027,
            2899 => 1027,
            2913 => 1033,
            2914 => 1033,
            3110 => 1109,
            3111 => 1110,
            3460 => 1274,
            3461 => 1274
        ];

        foreach ($data as $earnings => $expectedTax) {
            $tax = $this->IncomeTax->calculateTax($earnings, 'weekly', new \DateTime('2018-06-02'), [
                'type' => 'standard',
                'scale' => 1
            ]);
            $this->assertEquals($expectedTax, $tax, 'Scale 1 - Weekly Earnings: ' . $earnings);
        }
    }

    /**
     * Test Scale 2 withheld amounts - Where Tax Free Threshold is claimed
     *
     * Values taken from ATO's "Statement of formulas for calculating amounts to be withheld 2018-19" PDF
     * https://www.ato.gov.au/uploadedFiles/Content/MEI/downloads/Calculating-amounts-to-be-withheld-2018-19.pdf
     */
    public function testScaleTwo()
    {
        $data = [
            71 => 0,
            72 => 0,
            116 => 0,
            117 => 0,
            249 => 0,
            250 => 0,
            354 => 0,
            355 => 0,
            360 => 1,
            361 => 1,
            421 => 13,
            422 => 13,
            527 => 43,
            528 => 44,
            710 => 82,
            711 => 82,
            712 => 82,
            713 => 83,
            890 => 144,
            891 => 145,
            931 => 159,
            932 => 159,
            1281 => 280,
            1282 => 281,
            1379 => 314,
            1380 => 314,
            1729 => 435,
            1730 => 435,
            1844 => 480,
            1845 => 480,
            2119 => 587,
            2120 => 587,
            2490 => 732,
            2491 => 732,
            2652 => 795,
            2653 => 795,
            2736 => 828,
            2737 => 828,
            2898 => 891,
            2899 => 891,
            2913 => 897,
            2914 => 897,
            3110 => 973,
            3111 => 974,
            3460 => 1110,
            3461 => 1110
        ];

        foreach ($data as $earnings => $expectedTax) {
            $tax = $this->IncomeTax->calculateTax($earnings, 'weekly', new \DateTime('2018-06-02'), [
                'type' => 'standard',
                'scale' => 2
            ]);
            $this->assertEquals($expectedTax, $tax, 'Scale 2 - Weekly Earnings: ' . $earnings);
        }
    }

    /**
     * Test Scale 3 withheld amounts - Foreign resident
     *
     * Values taken from ATO's "Statement of formulas for calculating amounts to be withheld 2018-19" PDF
     * https://www.ato.gov.au/uploadedFiles/Content/MEI/downloads/Calculating-amounts-to-be-withheld-2018-19.pdf
     */
    public function testScaleThree()
    {
        $data = [
            71 => 23,
            72 => 23,
            116 => 38,
            117 => 38,
            249 => 81,
            250 => 81,
            354 => 115,
            355 => 115,
            360 => 117,
            361 => 117,
            421 => 137,
            422 => 137,
            527 => 171,
            528 => 172,
            710 => 231,
            711 => 231,
            712 => 231,
            713 => 232,
            890 => 289,
            891 => 290,
            931 => 303,
            932 => 303,
            1281 => 416,
            1282 => 417,
            1379 => 448,
            1380 => 448,
            1729 => 562,
            1730 => 563,
            1844 => 605,
            1845 => 605,
            2119 => 707,
            2120 => 707,
            2490 => 844,
            2491 => 844,
            2652 => 904,
            2653 => 904,
            2736 => 935,
            2737 => 935,
            2898 => 995,
            2899 => 995,
            2913 => 1000,
            2914 => 1001,
            3110 => 1073,
            3111 => 1074,
            3460 => 1203,
            3461 => 1203
        ];

        foreach ($data as $earnings => $expectedTax) {
            $tax = $this->IncomeTax->calculateTax($earnings, 'weekly', new \DateTime('2018-06-02'), [
                'type' => 'standard',
                'scale' => 3
            ]);
            $this->assertEquals($expectedTax, $tax, 'Scale 3 - Weekly Earnings: ' . $earnings);
        }
    }

    /**
     * Test Scale 4 withheld amounts - Where the TFN is not provided
     *
     * As the taxation rate is flat, we are only testing a subset of income amounts
     */
    public function testScaleFour()
    {
        $data = [
            71 => 33,
            249 => 117,
            360 => 169,
            527 => 247,
            712 => 334,
            931 => 437,
            1379 => 648,
            1844 => 866,
            2490 => 1170,
            2736 => 1285,
            2913 => 1369,
            3460 => 1626,
        ];

        foreach ($data as $earnings => $expectedTax) {
            $tax = $this->IncomeTax->calculateTax($earnings, 'weekly', new \DateTime('2018-06-02'), [
                'type' => 'standard',
                'scale' => '4 resident'
            ]);
            $this->assertEquals($expectedTax, $tax, 'Scale 4 - Weekly Earnings: ' . $earnings);
        }
    }

    /**
     * Test Scale 5 withheld amounts - Full Medicare exemption
     *
     * Values taken from ATO's "Statement of formulas for calculating amounts to be withheld 2018-19" PDF
     * https://www.ato.gov.au/uploadedFiles/Content/MEI/downloads/Calculating-amounts-to-be-withheld-2018-19.pdf
     */
    public function testScaleFive()
    {
        $data = [
            71 => 0,
            72 => 0,
            116 => 0,
            117 => 0,
            249 => 0,
            250 => 0,
            354 => 0,
            355 => 0,
            360 => 1,
            361 => 1,
            421 => 13,
            422 => 13,
            527 => 33,
            528 => 33,
            710 => 68,
            711 => 68,
            712 => 68,
            713 => 69,
            890 => 127,
            891 => 127,
            931 => 140,
            932 => 140,
            1281 => 255,
            1282 => 255,
            1379 => 287,
            1380 => 287,
            1729 => 400,
            1730 => 401,
            1844 => 443,
            1845 => 443,
            2119 => 545,
            2120 => 545,
            2490 => 682,
            2491 => 682,
            2652 => 742,
            2653 => 742,
            2736 => 773,
            2737 => 773,
            2898 => 833,
            2899 => 833,
            2913 => 838,
            2914 => 839,
            3110 => 911,
            3111 => 912,
            3460 => 1041,
            3461 => 1041
        ];

        foreach ($data as $earnings => $expectedTax) {
            $tax = $this->IncomeTax->calculateTax($earnings, 'weekly', new \DateTime('2018-06-02'), [
                'type' => 'standard',
                'scale' => 5
            ]);
            $this->assertEquals($expectedTax, $tax, 'Scale 5 - Weekly Earnings: ' . $earnings);
        }
    }

    /**
     * Test Scale 6 withheld amounts - Half Medicare exemption
     *
     * Values taken from ATO's "Statement of formulas for calculating amounts to be withheld 2018-19" PDF
     * https://www.ato.gov.au/uploadedFiles/Content/MEI/downloads/Calculating-amounts-to-be-withheld-2018-19.pdf
     */
    public function testScaleSix()
    {
        $data = [
            71 => 0,
            72 => 0,
            116 => 0,
            117 => 0,
            249 => 0,
            250 => 0,
            354 => 0,
            355 => 0,
            360 => 1,
            361 => 1,
            421 => 13,
            422 => 13,
            527 => 33,
            528 => 33,
            710 => 68,
            711 => 68,
            712 => 68,
            713 => 69,
            890 => 135,
            891 => 136,
            931 => 149,
            932 => 150,
            1281 => 267,
            1282 => 268,
            1379 => 300,
            1380 => 301,
            1729 => 418,
            1730 => 418,
            1844 => 461,
            1845 => 462,
            2119 => 566,
            2120 => 566,
            2490 => 707,
            2491 => 707,
            2652 => 768,
            2653 => 769,
            2736 => 800,
            2737 => 801,
            2898 => 862,
            2899 => 862,
            2913 => 867,
            2914 => 868,
            3110 => 942,
            3111 => 943,
            3460 => 1075,
            3461 => 1076
        ];

        foreach ($data as $earnings => $expectedTax) {
            $tax = $this->IncomeTax->calculateTax($earnings, 'weekly', new \DateTime('2018-06-02'), [
                'type' => 'standard',
                'scale' => 6
            ]);
            $this->assertEquals($expectedTax, $tax, 'Scale 6 - Weekly Earnings: ' . $earnings);
        }
    }
}
