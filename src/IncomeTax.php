<?php
namespace ABWeb\IncomeTax;

use ABWeb\IncomeTax\Exception\SourceException;
use ABWeb\IncomeTax\Exception\CalculationException;

class IncomeTax
{
    protected $coefficients = [];

    public function __construct($source = null)
    {
        if (!empty($source)) {
            $this->loadSource($source);
        }
    }

    public function loadSource($source = [])
    {
        if ($this->checkSource($source) === false) {
            return false;
        }

        $this->coefficients = $source;
        return true;
    }

    protected function checkSource($source)
    {
        if (is_array($source) === false) {
            throw new SourceException('The coefficients source must be an array.', 31201);
            return false;
        }

        // Determine format of array
        foreach ($source as $scale => $brackets) {
            if (!is_int($scale)) {
                throw new SourceException('The first array layer keys must represent the tax scale in the form of an integer. [Scale: ' . $scale . ']', 31202);
                return false;
            }
            if (!is_array($brackets)) {
                throw new SourceException('Each first array layer item must represent a tax scale in the form of an array of brackets. [Scale: ' . $scale . ']', 31203);
                return false;
            }
            $default_found = false;
            foreach ($brackets as $earnings => $coefficients) {
                if ($earnings === 0) {
                    $default_found = true;
                }
                if (!is_int($earnings) && !is_float($earnings)) {
                    throw new SourceException('The second array layer keys must represent the maximum weekly earnings in a bracket. This should be an integer or float. [Scale: ' . $scale . ', Earning: ' . $earnings . ']', 31204);
                    return false;
                }
                if (!is_array($coefficients)) {
                    throw new SourceException('Coefficients must be represented as a single one or two-value array made up of integers/floats. [Scale: ' . $scale . ', Earning: ' . $earnings . ']', 31205);
                    return false;
                }
                if (count($coefficients) > 2 || (!is_int($coefficients[0]) && !is_float($coefficients[0])) || (isset($coefficients[1]) && !is_int($coefficients[1]) && !is_float($coefficients[1]))) {
                    throw new SourceException('Invalid coefficients specification. [Scale: ' . $scale . ', Earning: ' . $earnings . ']', 31206);
                    return false;
                }
            }
            if ($default_found === false) {
                throw new SourceException('A default coefficient must be specified for high-earning users. This coefficient needs to have integer 0 as the earnings key. [Scale: ' . $scale . ']', 31207);
                return false;
            }
        }

        return true;
    }
}
