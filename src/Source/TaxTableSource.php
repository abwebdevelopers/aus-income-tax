<?php
namespace ABWeb\IncomeTax\Source;

interface TaxTableSource
{
    public function coefficients($scale = null);
    public function leapYearPayments($scale = null);
}
