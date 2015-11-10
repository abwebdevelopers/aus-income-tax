<?php
namespace ABWeb\IncomeTax\Tests\TestCase;

use ABWeb\IncomeTax\IncomeTax;
use ABWeb\IncomeTax\Source\TaxTables2015;

class SourceTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->IncomeTax = new IncomeTax;
    }

    public function testValidSource()
    {
        $loaded = $this->IncomeTax->loadSource(TaxTables2015::source());
        $this->assertTrue($loaded, 'Unable to load Tax Tables 2015 source');
    }

    /**
    * @expectedException \ABWeb\IncomeTax\Exception\SourceException
    * @expectedExceptionCode 31201
    **/
    public function testInvalidSource()
    {
        $this->IncomeTax->loadSource('invalid');
    }

    /**
    * @expectedException \ABWeb\IncomeTax\Exception\SourceException
    * @expectedExceptionCode 31202
    **/
    public function testInvalidScale()
    {
        $this->IncomeTax->loadSource([
            'wrong' => [ // Scale 1 - No tax-free threshold
                0 => [0.4900, 405.8080],
                45 => [0.1900, 0.1900],
                361 => [0.2321, 1.8961],
                932 => [0.3477, 43.6900],
                1188 => [0.3450, 41.1734],
                3111 => [0.3900, 94.6542]
            ]
        ]);
    }

    public function testScaleStringNum()
    {
        $loaded = $this->IncomeTax->loadSource([
            '1' => [ // Scale 1 - No tax-free threshold
                0 => [0.4900, 405.8080],
                45 => [0.1900, 0.1900],
                361 => [0.2321, 1.8961],
                932 => [0.3477, 43.6900],
                1188 => [0.3450, 41.1734],
                3111 => [0.3900, 94.6542]
            ]
        ]);

        $this->assertTrue($loaded);
    }

    /**
    * @expectedException \ABWeb\IncomeTax\Exception\SourceException
    * @expectedExceptionCode 31203
    **/
    public function testInvalidBrackets()
    {
        $this->IncomeTax->loadSource([
            1 => 'wrong' // Scale 1 - No tax-free threshold
        ]);
    }

    /**
    * @expectedException \ABWeb\IncomeTax\Exception\SourceException
    * @expectedExceptionCode 31205
    **/
    public function testInvalidBrackets2()
    {
        $this->IncomeTax->loadSource([
            1 => ['wrong'] // Scale 1 - No tax-free threshold
        ]);
    }

    /**
    * @expectedException \ABWeb\IncomeTax\Exception\SourceException
    * @expectedExceptionCode 31204
    **/
    public function testInvalidBrackets3()
    {
        $this->IncomeTax->loadSource([
            1 => [
                'wrong' => [0.900, 1.2345]
            ]
        ]);
    }

    /**
    * @expectedException \ABWeb\IncomeTax\Exception\SourceException
    * @expectedExceptionCode 31206
    **/
    public function testInvalidValues()
    {
        $this->IncomeTax->loadSource([
            1 => [
                100 => ['0.900', '1.2345']
            ]
        ]);
    }

    /**
    * @expectedException \ABWeb\IncomeTax\Exception\SourceException
    * @expectedExceptionCode 31206
    **/
    public function testInvalidValues2()
    {
        $this->IncomeTax->loadSource([
            1 => [
                100 => ['wrong', '1.2345']
            ]
        ]);
    }

    /**
    * @expectedException \ABWeb\IncomeTax\Exception\SourceException
    * @expectedExceptionCode 31207
    **/
    public function testNoDefaultCoefficient()
    {
        $this->IncomeTax->loadSource([
            1 => [
                45 => [0.1900, 0.1900]
            ]
        ]);
    }

    public function testNoSecondCoefficient()
    {
        $loaded = $this->IncomeTax->loadSource([
            1 => [
                0 => [0.1555],
                100 => [0.4900]
            ]
        ]);

        $this->assertTrue($loaded);
    }
}
