<?php
namespace ABWeb\IncomeTax;

use ABWeb\IncomeTax\Exception\SourceException;
use ABWeb\IncomeTax\Exception\CalculationException;

class IncomeTax
{
    protected $source = null;
    protected $validFrequencies = ['weekly', 'fortnightly', 'monthly', 'quarterly'];

    public function __construct($source = null)
    {
        if (!empty($source)) {
            $this->loadSource($source);
        }
    }

    public function loadSource(\ABWeb\IncomeTax\Source\TaxTableSource $source)
    {
        if ($this->checkSource($source) === false) {
            return false;
        }

        $this->source = $source;
        return true;
    }

    public function calculateTax($beforeTax = 0, $scale = 1, $frequency = 'weekly', $date = null)
    {
        if (!isset($this->source)) {
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
        if ($this->source->coefficients($scale) === false) {
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
                return $this->calculateWeeklyTax($beforeTax, $scale, $date);
                break;
        }
    }

    protected function calculateWeeklyTax($beforeTax, $scale, $date = null)
    {
        // Round to nearest dollar and add 99 cents
        $earnings = round($beforeTax, 0, PHP_ROUND_HALF_UP) + 0.99;
        $percentage = false;
        $subtraction = 0;

        // Find correct coefficients
        foreach ($this->source->coefficients($scale) as $bracket => $values) {
            if ($bracket === 0) {
                $default = $values;
                continue;
            }

            if ($earnings < $bracket) {
                $percentage = $values[0];
                if (isset($values[1])) {
                    $subtraction = $values[1];
                }
                break;
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
        $tax = ($earnings * $percentage) - $subtraction;

        // If it's a leap year, add additional

        return round($tax, 0, PHP_ROUND_HALF_UP);
    }
}
