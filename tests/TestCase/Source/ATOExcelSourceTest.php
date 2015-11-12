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


}
