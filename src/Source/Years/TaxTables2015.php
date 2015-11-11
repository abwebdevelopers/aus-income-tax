<?php
namespace ABWeb\IncomeTax\Source\Years;

use ABWeb\IncomeTax\Source\TaxTableSource;

class TaxTables2015 implements TaxTableSource
{
    protected $coefficients = [
        1 => [ // Scale 1 - No tax-free threshold
            0 => [0.4900, 405.8080],
            45 => [0.1900, 0.1900],
            361 => [0.2321, 1.8961],
            932 => [0.3477, 43.6900],
            1188 => [0.3450, 41.1734],
            3111 => [0.3900, 94.6542]
        ],
        2 => [ // Scale 2 - Claimed tax-free threshold
            0 => [0.4900, 577.3662],
            355 => [0, 0],
            395 => [0.1900, 67.4635],
            493 => [0.2900, 106.9673],
            711 => [0.2100, 67.4642],
            1282 => [0.3477, 165.4431],
            1538 => [0.3450, 161.9815],
            3461 => [0.3900, 231.2123]
        ],
        3 => [ // Scale 3 - Foreign residents
            0 => [0.4700, 415.3846],
            1538 => [0.3250, 0.3250],
            3461 => [0.3700, 69.2308]
        ],
        4 => [ // Scale 4 - No TFN Provided - resident
            0 => [0.4900]
        ],
        7 => [ // Scale 4 - No TFN Provided - foreign resident
            0 => [0.4700]
        ]
    ];

    protected $leapYearExtra = [
        'weekly' => [
            0 => 10,
            724 => 0,
            1524 => 3,
            3449 => 4
        ],
        'fortnightly' => [

        ]
    ];

    public function coefficients($scale = null)
    {
        if ($scale !== null) {
            return (isset($this->coefficients[$scale])) ? $this->coefficients[$scale] : false;
        } else {
            return $this->coefficients;
        }
    }

    public function leapYearPayments($frequency = null)
    {
        if ($frequency !== null) {
            return (isset($this->leapYearExtra[$frequency])) ? $this->leapYearExtra[$frequency] : false;
        } else {
            return $this->leapYearExtra;
        }
    }
}
