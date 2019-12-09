<?php
namespace ABWebDevelopers\AusIncomeTax\Tests\TestCase;

use PHPUnit\Framework\TestCase;
use ABWebDevelopers\AusIncomeTax\IncomeTax;
use ABWebDevelopers\AusIncomeTax\Source\ATOExcelSource;

class FortnightlyTest extends TestCase
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
     * https://www.ato.gov.au/uploadedFiles/Content/MEI/downloads/Calculating-amounts-to-be-withheld-from-1-July-2018.pdf
     */
    public function testScaleOne()
    {
        $data = [
            142 => 26,
            144 => 28,
            232 => 48,
            234 => 48,
            498 => 110,
            500 => 112,
            708 => 160,
            710 => 160,
            720 => 162,
            722 => 164,
            842 => 204,
            844 => 206,
            1054 => 278,
            1056 => 280,
            1420 => 406,
            1422 => 406,
            1424 => 408,
            1426 => 408,
            1780 => 532,
            1782 => 532,
            1862 => 560,
            1864 => 560,
            2562 => 802,
            2564 => 802,
            2758 => 868,
            2760 => 870,
            3458 => 1142,
            3460 => 1142,
            3688 => 1232,
            3690 => 1232,
            4238 => 1446,
            4240 => 1446,
            4980 => 1736,
            4982 => 1736,
            5304 => 1862,
            5306 => 1862,
            5472 => 1928,
            5474 => 1928,
            5796 => 2054,
            5798 => 2054,
            5826 => 2066,
            5828 => 2066,
            6220 => 2218,
            6222 => 2220,
            6920 => 2548,
            6922 => 2548
        ];

        foreach ($data as $earnings => $expectedTax) {
            $tax = $this->IncomeTax->calculateTax($earnings, 'fortnightly', new \DateTime('2018-06-02'), [
                'type' => 'standard',
                'scale' => 1
            ]);
            $this->assertEquals($expectedTax, $tax, 'Scale 1 - Fortnightly Earnings: ' . $earnings);
        }
    }

    /**
     * Test Scale 2 withheld amounts - Where Tax Free Threshold is claimed
     *
     * Values taken from ATO's "Statement of formulas for calculating amounts to be withheld 2018-19" PDF
     * https://www.ato.gov.au/uploadedFiles/Content/MEI/downloads/Calculating-amounts-to-be-withheld-from-1-July-2018.pdf
     */
    public function testScaleTwo()
    {
        $data = [
            142 => 0,
            144 => 0,
            232 => 0,
            234 => 0,
            498 => 0,
            500 => 0,
            708 => 0,
            710 => 0,
            720 => 2,
            722 => 2,
            842 => 26,
            844 => 26,
            1054 => 86,
            1056 => 88,
            1420 => 164,
            1422 => 164,
            1424 => 164,
            1426 => 166,
            1780 => 288,
            1782 => 290,
            1862 => 318,
            1864 => 318,
            2562 => 560,
            2564 => 562,
            2758 => 628,
            2760 => 628,
            3458 => 870,
            3460 => 870,
            3688 => 960,
            3690 => 960,
            4238 => 1174,
            4240 => 1174,
            4980 => 1464,
            4982 => 1464,
            5304 => 1590,
            5306 => 1590,
            5472 => 1656,
            5474 => 1656,
            5796 => 1782,
            5798 => 1782,
            5826 => 1794,
            5828 => 1794,
            6220 => 1946,
            6222 => 1948,
            6920 => 2220,
            6922 => 2220
        ];

        foreach ($data as $earnings => $expectedTax) {
            $tax = $this->IncomeTax->calculateTax($earnings, 'fortnightly', new \DateTime('2018-06-02'), [
                'type' => 'standard',
                'scale' => 2
            ]);
            $this->assertEquals($expectedTax, $tax, 'Scale 2 - Fortnightly Earnings: ' . $earnings);
        }
    }

    /**
     * Test Scale 3 withheld amounts - Foreign resident
     *
     * Values taken from ATO's "Statement of formulas for calculating amounts to be withheld 2018-19" PDF
     * https://www.ato.gov.au/uploadedFiles/Content/MEI/downloads/Calculating-amounts-to-be-withheld-from-1-July-2018.pdf
     */
    public function testScaleThree()
    {
        $data = [
            142 => 46,
            144 => 46,
            232 => 76,
            234 => 76,
            498 => 162,
            500 => 162,
            708 => 230,
            710 => 230,
            720 => 234,
            722 => 234,
            842 => 274,
            844 => 274,
            1054 => 342,
            1056 => 344,
            1420 => 462,
            1422 => 462,
            1424 => 462,
            1426 => 464,
            1780 => 578,
            1782 => 580,
            1862 => 606,
            1864 => 606,
            2562 => 832,
            2564 => 834,
            2758 => 896,
            2760 => 896,
            3458 => 1124,
            3460 => 1126,
            3688 => 1210,
            3690 => 1210,
            4238 => 1414,
            4240 => 1414,
            4980 => 1688,
            4982 => 1688,
            5304 => 1808,
            5306 => 1808,
            5472 => 1870,
            5474 => 1870,
            5796 => 1990,
            5798 => 1990,
            5826 => 2000,
            5828 => 2002,
            6220 => 2146,
            6222 => 2148,
            6920 => 2406,
            6922 => 2406
        ];

        foreach ($data as $earnings => $expectedTax) {
            $tax = $this->IncomeTax->calculateTax($earnings, 'fortnightly', new \DateTime('2018-06-02'), [
                'type' => 'standard',
                'scale' => 3
            ]);
            $this->assertEquals($expectedTax, $tax, 'Scale 3 - Fortnightly Earnings: ' . $earnings);
        }
    }
}
