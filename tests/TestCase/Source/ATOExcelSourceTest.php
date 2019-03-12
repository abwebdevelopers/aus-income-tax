<?php
namespace ABWebDevelopers\AusIncomeTax\Tests\TestCase;

use PHPUnit\Framework\TestCase;
use ABWebDevelopers\AusIncomeTax\Source\ATOExcelSource;
use ABWebDevelopers\AusIncomeTax\Exception\SourceException;

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
        $this->expectException(SourceException::class);
        $this->expectExceptionCode(2002);

        $this->ATOExcelSource->loadStandardFile($this->sourceDirectory . 'MISSING_FILE.xlsx');
    }

    public function testInvalidSource()
    {
        $this->expectException(SourceException::class);
        $this->expectExceptionCode(2002);

        $this->ATOExcelSource->loadStandardFile($this->sourceDirectory . 'NAT_INVALID.txt');
    }

    public function testInvalidSourceRows()
    {
        $this->expectException(SourceException::class);
        $this->expectExceptionCode(2005);

        $this->ATOExcelSource->loadStandardFile($this->sourceDirectory . 'NAT_INVALID.xlsx');
    }

    public function testCoefficientsArrayParameter()
    {
        $coefficients = $this->ATOExcelSource->coefficients(
            1000,
            'standard',
            '1'
        );

        $this->assertEquals([
            'percentage' => 0.3450,
            'subtraction' => 41.7311
        ], $coefficients);
    }

    public function testCoefficientsInvalidType()
    {
        $this->expectException(SourceException::class);
        $this->expectExceptionCode(2001);

        $this->ATOExcelSource->coefficients(
            1000,
            'not-exist',
            '1'
        );
    }

    public function testCoefficientsInvalidScale()
    {
        $this->expectException(SourceException::class);
        $this->expectExceptionCode(2001);

        $this->ATOExcelSource->coefficients(
            1000,
            'standard',
            '9'
        );
    }

    public function testNoTFN()
    {
        $threshold = $this->ATOExcelSource->determineThreshold(
            false
        );

        $this->assertEquals('standard', $threshold['type']);
        $this->assertEquals('4 resident', $threshold['scale']);

        $threshold = $this->ATOExcelSource->determineThreshold(
            false,
            true
        );

        $this->assertEquals('standard', $threshold['type']);
        $this->assertEquals('4 non resident', $threshold['scale']);
    }

    public function testTaxFreeThreshold()
    {
        $threshold = $this->ATOExcelSource->determineThreshold(
            true
        );

        $this->assertEquals('standard', $threshold['type']);
        $this->assertEquals('2', $threshold['scale']);

        // Foreign residents cannot claim the tax free threshold
        $threshold = $this->ATOExcelSource->determineThreshold(
            true,
            true
        );

        $this->assertEquals('standard', $threshold['type']);
        $this->assertEquals('3', $threshold['scale']);
    }

    public function testNoTaxFreeThreshold()
    {
        $threshold = $this->ATOExcelSource->determineThreshold(
            true,
            false,
            false
        );

        $this->assertEquals('standard', $threshold['type']);
        $this->assertEquals('1', $threshold['scale']);

        $threshold = $this->ATOExcelSource->determineThreshold(
            true,
            true,
            false
        );

        $this->assertEquals('standard', $threshold['type']);
        $this->assertEquals('3', $threshold['scale']);
    }

    public function testDebts()
    {
        $threshold = $this->ATOExcelSource->determineThreshold(
            true,
            false,
            true,
            null,
            null,
            true
        );

        $this->assertEquals('help', $threshold['type']);
        $this->assertEquals('2', $threshold['scale']);

        $threshold = $this->ATOExcelSource->determineThreshold(
            true,
            false,
            true,
            null,
            null,
            false,
            true
        );

        $this->assertEquals('sfss', $threshold['type']);
        $this->assertEquals('2', $threshold['scale']);

        $threshold = $this->ATOExcelSource->determineThreshold(
            true,
            false,
            true,
            null,
            null,
            true,
            true
        );

        $this->assertEquals('combo', $threshold['type']);
        $this->assertEquals('2', $threshold['scale']);

        $threshold = $this->ATOExcelSource->determineThreshold(
            true,
            true,
            true,
            null,
            null,
            true,
            true
        );

        $this->assertEquals('combo', $threshold['type']);
        $this->assertEquals('3', $threshold['scale']);

        // Seniors with HELP and SFSS debt still get charged seniors tax rate
        $threshold = $this->ATOExcelSource->determineThreshold(
            true,
            true,
            true,
            'single',
            null,
            true,
            true
        );

        $this->assertEquals('seniors', $threshold['type']);
        $this->assertEquals('single', $threshold['scale']);
    }

    public function testSeniors()
    {
        $threshold = $this->ATOExcelSource->determineThreshold(
            true,
            false,
            true,
            'single'
        );

        $this->assertEquals('seniors', $threshold['type']);
        $this->assertEquals('single', $threshold['scale']);

        $threshold = $this->ATOExcelSource->determineThreshold(
            true,
            false,
            true,
            'illness-separated'
        );

        $this->assertEquals('seniors', $threshold['type']);
        $this->assertEquals('illness-separated', $threshold['scale']);

        $threshold = $this->ATOExcelSource->determineThreshold(
            true,
            false,
            true,
            'couple'
        );

        $this->assertEquals('seniors', $threshold['type']);
        $this->assertEquals('member of a couple', $threshold['scale']);

        // A senior who has not provided their TFN will be taxed at the no-TFN rate
        $threshold = $this->ATOExcelSource->determineThreshold(
            false,
            false,
            true,
            'illness-separated'
        );

        $this->assertEquals('standard', $threshold['type']);
        $this->assertEquals('4 resident', $threshold['scale']);
    }

    public function testMedicareLevy()
    {
        $threshold = $this->ATOExcelSource->determineThreshold(
            true,
            false,
            true,
            null,
            'full',
            true,
            false
        );

        $this->assertEquals('help', $threshold['type']);
        $this->assertEquals('5', $threshold['scale']);

        $threshold = $this->ATOExcelSource->determineThreshold(
            true,
            false,
            true,
            null,
            'half',
            true,
            true
        );

        $this->assertEquals('combo', $threshold['type']);
        $this->assertEquals('6', $threshold['scale']);

        $threshold = $this->ATOExcelSource->determineThreshold(
            true,
            true,
            true,
            null,
            'half',
            true,
            true
        );

        $this->assertEquals('combo', $threshold['type']);
        $this->assertEquals('6', $threshold['scale']);
    }
}
