<?php
namespace ABWebDevelopers\AusIncomeTax\Exception;

class CalculationException extends \Exception
{
    public function __construct($message = null, $code = 31300, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function __toString()
    {
        return '<strong>Income Tax Calculation Exception:</strong> ' . $this->message() . "\n";
    }
}
