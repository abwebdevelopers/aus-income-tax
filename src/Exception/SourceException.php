<?php
class SourceException extends \Exception
{
    public function __construct($message = null, $code = 60511, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function __toString()
    {
        return '<strong>Income Tax Source Exception:</strong> ' . $this->message() . "\n";
    }
}
