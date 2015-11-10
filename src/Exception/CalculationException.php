<?php
class CalculationException extends \Exception
{
    public function __construct($message = null, $code = 60512, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function __toString()
    {
        return '<strong>Income Tax Calculation Exception:</strong> ' . $this->message() . "\n";
    }
}
