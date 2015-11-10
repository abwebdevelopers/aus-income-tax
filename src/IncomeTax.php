<?php
namespace ABWeb\IncomeTax;

use ABWeb\IncomeTax\Exception\SourceException;
use ABWeb\IncomeTax\Exception\CalculationException;

class IncomeTax
{
    protected $coefficients = null;
    protected $validFrequencies = ['weekly', 'fortnightly', 'monthly', 'quarterly'];

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

    public function calculateTax($beforeTax = 0, $scale = 1, $frequency = 'weekly', $date = null)
    {
        if (!isset($this->coefficients)) {
            throw new SourceException('No source specified.');
            return false;
        }

        if (is_array($beforeTax)) {
            $settings = $beforeTax;
            $beforeTax = (isset($settings['beforeTax'])) ? floatval($settings['beforeTax']) : 0;
            $frequency = (isset($settings['frequency']) && in_array($settings['frequency'], $this->validFrequencies)) ? $settings['frequency'] : 'weekly';
            $date = (isset($settings['date'])) ? new \DateTime($date) : new \DateTime;

            if (!isset($settings['scale'])) {
                unset($settings['beforeTax']);
                unset($settings['frequency']);
                unset($settings['date']);
                $scale = $this->determineScale($settings);
            } else {
                $scale = $settings['scale'];
            }
        } else {
            if (!isset($date)) {
                $date = new \DateTime;
            }
        }

        // Validate values
        if (!is_int($beforeTax) && !is_float($beforeTax)) {
            throw new CalculationException('Before tax amount must be a number value', 31301);
            return false;
        }
        if ($beforeTax < 0) {
            throw new CalculationException('Before tax amount cannot be negative', 31302);
            return false;
        }
        if (!in_array($frequency, $this->validFrequencies)) {
            throw new CalculationException('Invalid payment frequency specified - must be "weekly", "fortnightly", "monthly" or "quarterly"', 31303);
            return false;
        }
        if (!isset($this->coefficients[$scale])) {
            throw new SourceException('Scale ' . $scale . ' does not exist in the source.', 31208);
            return false;
        }
        if ($date === false) {
            throw new CalculationException('Invalid payment date specified.', 312304);
            return false;
        }

        // Calculate tax
        switch ($frequency) {
            case 'weekly':
            default:
                return $this->calculateWeeklyTax($beforeTax, $scale);
                break;
        }
    }

    protected function calculateWeeklyTax($beforeTax, $scale)
    {
        // Round to nearest dollar and add 99 cents
        $earnings = round($beforeTax, 0, PHP_ROUND_HALF_UP) + 0.99;
        $percentage = false;
        $subtraction = 0;

        // Find correct coefficients
        foreach ($this->coefficients[$scale] as $bracket => $values) {
            if ($bracket === 0) {
                $default = $values;
                continue;
            }

            if ($earnings < $bracket) {
                $percentage = $values[0];
                if (isset($values[1])) {
                    $subtraction = $values[1];
                }
            }
        }

        // If the amount did not fall in defined brackets, use the default
        if ($percentage === false) {
            $percentage = $default[0];
            if (isset($default[1])) {
                $subtraction = $default[1];
            }
        }

        // Calculate tax
        if ($percentage === 0) {
            return 0;
        }
        $tax = ($beforeTax * $percentage) - $subtraction;
        return round($tax, 0, PHP_ROUND_HALF_UP);
    }
}
