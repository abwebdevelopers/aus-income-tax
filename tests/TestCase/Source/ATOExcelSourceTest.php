<?php
namespace ABWeb\IncomeTax\Tests\TestCase;

use ABWeb\IncomeTax\Source\ATOExcelSource;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

class ATOExcelSourceTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->ATOExcelSource = new ATOExcelSource([
            'standardFile' => dirname(dirname(dirname(__DIR__))) . DS . 'ext' . DS . '2015' . DS . 'NAT_1004_0.xlsx',
            'helpSfssFile' => dirname(dirname(dirname(__DIR__))) . DS . 'ext' . DS . '2015' . DS . 'NAT_3539_0.xlsx',
            'seniorsFile' => dirname(dirname(dirname(__DIR__))) . DS . 'ext' . DS . '2015' . DS . 'NAT_4466_0.xlsx',
        ]);
    }

    /**
    * @expectedException \ABWeb\IncomeTax\Exception\SourceException
    * @expectedExceptionCode 31250
    **/
    public function testMissingSource()
    {
        $this->ATOExcelSource->loadStandardFile(dirname(dirname(__DIR__)) . DS . 'ext' . DS . 'MISSING_FILE.xlsx');
    }

    /**
    * @expectedException \ABWeb\IncomeTax\Exception\SourceException
    * @expectedExceptionCode 31251
    **/
    public function testInvalidSource()
    {
        $this->ATOExcelSource->loadStandardFile(dirname(dirname(__DIR__)) . DS . 'ext' . DS . 'NAT_INVALID.txt');
    }

    /**
    * @expectedException \ABWeb\IncomeTax\Exception\SourceException
    * @expectedExceptionCode 31253
    **/
    public function testInvalidSourceRows()
    {
        $this->ATOExcelSource->loadStandardFile(dirname(dirname(__DIR__)) . DS . 'ext' . DS . 'NAT_INVALID.xlsx');
    }

    public function testCoefficientsArrayParameter()
    {
        $coefficients = $this->ATOExcelSource->coefficients([
            'amountBeforeTax' => 1000,
            'type' => 'standard',
            'scale' => 1
        ]);

        $this->assertEquals([
            'percentage' => 0.3450,
            'subtraction' => 41.1734
        ], $coefficients);
    }

    public function testCoefficientsInvalidTypeAndScale()
    {
        $this->assertFalse($this->ATOExcelSource->coefficients([
            'amountBeforeTax' => 1000,
            'type' => 'not-exist',
            'scale' => 1
        ]));
        $this->assertFalse($this->ATOExcelSource->coefficients([
            'amountBeforeTax' => 1000,
            'type' => 'standard',
            'scale' => 9
        ]));
    }

    public function testNoTFN()
    {
        $scale = $this->ATOExcelSource->determineScale([
            'tfnProvided' => false
        ]);

        $this->assertEquals('standard', $scale['type']);
        $this->assertEquals('4 resident', $scale['scale']);

        $scale = $this->ATOExcelSource->determineScale([
            'foreignResident' => true,
            'tfnProvided' => false
        ]);

        $this->assertEquals('standard', $scale['type']);
        $this->assertEquals('4 non resident', $scale['scale']);
    }

    public function testTaxFreeThreshold()
    {
        $scale = $this->ATOExcelSource->determineScale([
            'tfnProvided' => true
        ]);

        $this->assertEquals('standard', $scale['type']);
        $this->assertEquals('2', $scale['scale']);

        // Foreign residents cannot claim the tax free threshold
        $scale = $this->ATOExcelSource->determineScale([
            'foreignResident' => true,
            'tfnProvided' => true
        ]);

        $this->assertEquals('standard', $scale['type']);
        $this->assertEquals('3', $scale['scale']);
    }

    public function testNoTaxFreeThreshold()
    {
        $scale = $this->ATOExcelSource->determineScale([
            'tfnProvided' => true,
            'taxFreeThreshold' => false
        ]);

        $this->assertEquals('standard', $scale['type']);
        $this->assertEquals('1', $scale['scale']);

        $scale = $this->ATOExcelSource->determineScale([
            'foreignResident' => true,
            'tfnProvided' => true,
            'taxFreeThreshold' => false
        ]);

        $this->assertEquals('standard', $scale['type']);
        $this->assertEquals('3', $scale['scale']);
    }

    public function testDebts()
    {
        $scale = $this->ATOExcelSource->determineScale([
            'tfnProvided' => true,
            'helpDebt' => true
        ]);

        $this->assertEquals('help', $scale['type']);
        $this->assertEquals('2', $scale['scale']);

        $scale = $this->ATOExcelSource->determineScale([
            'tfnProvided' => true,
            'sfssDebt' => true
        ]);

        $this->assertEquals('sfss', $scale['type']);
        $this->assertEquals('2', $scale['scale']);

        $scale = $this->ATOExcelSource->determineScale([
            'tfnProvided' => true,
            'helpDebt' => true,
            'sfssDebt' => true
        ]);

        $this->assertEquals('combo', $scale['type']);
        $this->assertEquals('2', $scale['scale']);

        $scale = $this->ATOExcelSource->determineScale([
            'foreignResident' => true,
            'tfnProvided' => true,
            'helpDebt' => true,
            'sfssDebt' => true
        ]);

        $this->assertEquals('combo', $scale['type']);
        $this->assertEquals('3', $scale['scale']);

        // Seniors with HELP and SFSS debt still get charged seniors tax rate
        $scale = $this->ATOExcelSource->determineScale([
            'foreignResident' => true,
            'tfnProvided' => true,
            'helpDebt' => true,
            'sfssDebt' => true,
            'seniorsOffset' => 'single'
        ]);

        $this->assertEquals('seniors', $scale['type']);
        $this->assertEquals('single', $scale['scale']);
    }

    public function testSeniors()
    {
        $scale = $this->ATOExcelSource->determineScale([
            'seniorsOffset' => 'single'
        ]);

        $this->assertEquals('seniors', $scale['type']);
        $this->assertEquals('single', $scale['scale']);

        $scale = $this->ATOExcelSource->determineScale([
            'seniorsOffset' => 'illness-separated'
        ]);

        $this->assertEquals('seniors', $scale['type']);
        $this->assertEquals('illness-separated', $scale['scale']);

        $scale = $this->ATOExcelSource->determineScale([
            'seniorsOffset' => 'couple'
        ]);

        $this->assertEquals('seniors', $scale['type']);
        $this->assertEquals('member of a couple', $scale['scale']);

        $scale = $this->ATOExcelSource->determineScale([
            'tfnProvided' => false,
            'seniorsOffset' => 'illness-separated'
        ]);

        $this->assertEquals('standard', $scale['type']);
        $this->assertEquals('4 resident', $scale['scale']);
    }

    public function testMedicareLevy()
    {
        $scale = $this->ATOExcelSource->determineScale([
            'tfnProvided' => true,
            'helpDebt' => true,
            'medicareLevyExemption' => 'full'
        ]);

        $this->assertEquals('help', $scale['type']);
        $this->assertEquals('5', $scale['scale']);

        $scale = $this->ATOExcelSource->determineScale([
            'tfnProvided' => true,
            'helpDebt' => true,
            'sfssDebt' => true,
            'medicareLevyExemption' => 'half'
        ]);

        $this->assertEquals('combo', $scale['type']);
        $this->assertEquals('6', $scale['scale']);

        $scale = $this->ATOExcelSource->determineScale([
            'foreignResident' => true,
            'tfnProvided' => true,
            'helpDebt' => true,
            'sfssDebt' => true,
            'medicareLevyExemption' => 'half'
        ]);

        $this->assertEquals('combo', $scale['type']);
        $this->assertEquals('6', $scale['scale']);
    }
}
