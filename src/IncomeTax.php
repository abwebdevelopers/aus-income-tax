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
        $this->source = $source;
        return true;
    }

    public function calculateTax($beforeTax = 0, $frequency = 'weekly', $date = null, $type = null, $scale = null)
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
            $type = (isset($settings['type'])) ? $settings['type'] : 'standard';
            $scale = (isset($settings['scale'])) ? $settings['scale'] : 1;

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
            $type = (isset($type)) ? $type : 'standard';
            $scale = (isset($scale)) ? $scale : 1;
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
        if ($date === false) {
            throw new CalculationException('Invalid payment date specified.', 312304);
            return false;
        }

        // Calculate tax
        switch ($frequency) {
            case 'weekly':
            default:
                return $this->calculateWeeklyTax($beforeTax, $date, $type, $scale);
                break;
        }
    }

    protected function calculateWeeklyTax($beforeTax, $date, $type, $scale)
    {
        if ($scale === '4 resident' || $scale === '4 non resident') {
            // Scale 4 earnings have all cents ignored
            $earnings = floor($beforeTax);
        } else {
            // Round to nearest dollar and add 99 cents
            $earnings = round($beforeTax, 0, PHP_ROUND_HALF_UP) + 0.99;
        }

        // Retrieve coefficients
        $coefficients = $this->source->coefficients($beforeTax, $type, $scale);
        extract($coefficients);

        // Calculate tax
        if ($percentage === 0) {
            return 0;
        }

        $tax = ($earnings * $percentage) - $subtraction;

        // If it's a leap year, add additional

        if ($scale === '4 resident' || $scale === '4 non resident') {
            // When scale 4 is used, any cents in the tax must be ignored
            return floor($tax);
        } else {
            return round($tax, 0, PHP_ROUND_HALF_UP);
        }
    }
}
