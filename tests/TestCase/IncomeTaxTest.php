<?php
namespace ABWebDevelopers\AusIncomeTax\Tests\TestCase;

use ABWebDevelopers\AusIncomeTax\Exception\CalculationException;
use PHPUnit\Framework\TestCase;
use ABWebDevelopers\AusIncomeTax\IncomeTax;
use ABWebDevelopers\AusIncomeTax\Source\ATOExcelSource;

class IncomeTaxTest extends TestCase
{
    public function setUp(): void
    {
        $this->IncomeTax = new IncomeTax(new ATOExcelSource([
            'standardFile' => __DIR__ . '/../../resources/tax-tables/2018-19/NAT_1004_2018.xlsx',
            'helpSfssFile' => __DIR__ . '/../../resources/tax-tables/2018-19/NAT_3539_2018.xlsx',
            'seniorsFile' => __DIR__ . '/../../resources/tax-tables/2018-19/NAT_4466_2018.xlsx',
        ]));
    }

    public function testNoSource()
    {
        $this->expectException(CalculationException::class);
        $this->expectExceptionCode(2002);

        $incomeTax = new IncomeTax;
        $incomeTax->calculateTax(1000);
    }

    public function testNegativeGross()
    {
        $this->expectException(CalculationException::class);
        $this->expectExceptionCode(1001);

        $this->IncomeTax->calculateTax(-500);
    }

    public function testInvalidFrequency()
    {
        $this->expectException(CalculationException::class);
        $this->expectExceptionCode(1002);

        $this->IncomeTax->calculateTax(1000, 'bi-monthly');
    }

    public function testValidParametersForScaleDetection()
    {
        $tax = $this->IncomeTax->calculateTax(1000, 'weekly', new \DateTime('2019-07-01'), [
            'tfnProvided' => true,
            'foreignResident' => false,
            'taxFreeThreshold' => true,
            'seniorsOffset' => 'couple',
            'medicareLevyExemption' => 'full',
            'helpDebt' => false,
            'sfssDebt' => false,
        ]);

        $this->assertEquals(183, $tax);
    }

    public function testInvalidParametersForScaleDetection()
    {
        $this->expectException(CalculationException::class);
        $this->expectExceptionCode(2001);

        $this->IncomeTax->calculateTax(1000, 'weekly', null, [
            'tfnProvided' => true,
            'foreignResident' => false,
            'taxFreeThreshold' => true,
            'seniorsOffset' => 'couple',
            'medicareLevyExemption' => 'full',
            'helpDebt' => false,
            // Invalid data
            'invalid' => true,
        ]);
    }
}
