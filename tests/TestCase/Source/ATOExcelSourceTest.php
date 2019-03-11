<?php
namespace ABWebDevelopers\AusIncomeTax\Tests\TestCase;

use PHPUnit\Framework\TestCase;
use ABWebDevelopers\AusIncomeTax\Source\ATOExcelSource;

class ATOExcelSourceTest extends TestCase
{
    /**
     * Directory where Excel Source test fixtures are stored
     *
     * @var string
     */
    public $sourceDirectory;

    public function setUp(): void
    {
        if (!defined('DS')) {
            define('DS', $_ENV['DIRECTORY_SEPARATOR'] ?? '/');
        }

        $this->sourceDirectory = implode(DS, [
            dirname(dirname(__DIR__)),
            'fixtures',
            'tax-tables',
        ]) . DS;

        $this->ATOExcelSource = new ATOExcelSource([
            'standardFile' => $this->sourceDirectory . 'NAT_1004_2018.xlsx',
            'helpSfssFile' => $this->sourceDirectory . 'NAT_3539_2018.xlsx',
            'seniorsFile' => $this->sourceDirectory . 'NAT_4466_2018.xlsx',
        ]);
    }

    public function testMissingSource()
    {
        $this->expectException(\ABWebDevelopers\AusIncomeTax\Exception\SourceException::class);
        $this->expectExceptionCode(31250);

        $this->ATOExcelSource->loadStandardFile($this->sourceDirectory . 'MISSING_FILE.xlsx');
    }

    public function testInvalidSource()
    {
        $this->expectException(\ABWebDevelopers\AusIncomeTax\Exception\SourceException::class);
        $this->expectExceptionCode(31251);

        $this->ATOExcelSource->loadStandardFile($this->sourceDirectory . 'NAT_INVALID.txt');
    }

    public function testInvalidSourceRows()
    {
        $this->expectException(\ABWebDevelopers\AusIncomeTax\Exception\SourceException::class);
        $this->expectExceptionCode(31253);

        $this->ATOExcelSource->loadStandardFile($this->sourceDirectory . 'NAT_INVALID.xlsx');
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
            'subtraction' => 41.7311
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
