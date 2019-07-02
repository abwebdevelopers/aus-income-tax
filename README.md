# Australian Income Tax Calculator

[<img src="https://api.travis-ci.org/ABWebDevelopers/aus-income-tax.svg?branch=master" alt="Build Status">](https://travis-ci.org/ABWebDevelopers/aus-income-tax)
[![codecov](https://codecov.io/gh/ABWebDevelopers/aus-income-tax/branch/master/graph/badge.svg)](https://codecov.io/gh/ABWebDevelopers/aus-income-tax)

Calculates withheld amounts from gross income as per the Australian Tax Office PAYG (pay-as-you-go) tax tables (currently on the 2018-2019 financial year).

## Requirements

- PHP 7.1 or above

If using the included Excel Spreadsheet source reader, you will also need the following PHP extensions enabled:

- `ctype`
- `dom`
- `gd`
- `iconv`
- `fileinfo`
- `libxml`
- `mbstring`
- `SimpleXML`
- `xml`
- `xmlreader`
- `xmlwriter`
- `zip`
- `zlib`

## Installation

Include this library in your application through [Composer](https://getcomposer.org):

```
composer require abwebdevelopers/aus-income-tax
```

## Usage

The library requires the published formulas from the Australian Tax Office in order to calculate the withheld amounts from gross income. These are generally published shortly before the end of the financial year. We have provided the latest files in the `resources/tax-tables` folder.

The codes that the ATO uses are as follows:

| Code | Contains |
| ---- | -------- |
| NAT 1004 | Standard formula for working out income tax. |
| NAT 3539 | Formula for working out income tax for people who claim a HELP (Higher Education Loan Program), SFSS (Student Financial Supplement Scheme) or other student assistance debt. |
| NAT 4466 | Formula for working out income tax for seniors and pensioners |

The easiest way to use this library is to use the Excel Spreadsheet reader to automatically feed this formula into the library:

```php
<?php
use ABWebDevelopers\AusIncomeTax\IncomeTax;
use ABWebDevelopers\AusIncomeTax\Source\ATOExcelSource;

$incomeTax = new IncomeTax(new ATOExcelSource([
    'standardFile' => 'resources/tax-tables/2018-19/NAT_1004_2018.xlsx',
    'helpSfssFile' => 'resources/tax-tables/2018-19/NAT_3539_2018.xlsx',
    'seniorsFile' => 'resources/tax-tables/2018-19/NAT_4466_2018.xlsx'
]));
```

Once loaded, you can calculate the tax withheld amount of a wage using the following:

```php
$tax = $this->IncomeTax->calculateTax(
    1000,                         // The gross wage
    'weekly',                     // The pay cycle - must be either `weekly`, `fortnightly`, `monthly` or `quarterly`
    new DateTime('2018-06-02'),   // The payment date
    [
        'type' => 'standard',     // The type of taxation - either `standard`, `help`, `sfss`, `combo` or `seniors`
        'scale' => '1'            // The taxation scale - `1` (tax free threshold not claimed), `2` (tax free threshold claimed), `3` (foreign resident), `4` (no TFN), `5` (full medicare exemption), `5` (half medicare exemption) 
    ]
);
```

This should return an `integer` value of the amount of tax to be withheld for the gross income.

## Exception codes

### \ABWebDevelopers\AusIncomeTax\Exception\CalculationException

| Code | Message |
| ---- | ------- |
| 1000 | Default error code for calculation errors. |
| 1001 | Gross amount cannot be negative. |
| 2001 | Invalid threshold type or scale provided. |

### \ABWebDevelopers\AusIncomeTax\Exception\SourceException

| Code | Message |
| ---- | ------- |
| 2000 | Default error code for source errors. |
| 2001 | Invalid threshold type or scale provided. |
| 2002 | Missing or invalid source file provided.  |
| 2003 | Invalid seniors offset value. |
| 2004 | Invalid Medicare Levy Exemption value. |
| 2005 | Malformed source file provided. |

## Disclaimer

Whilst great care has been taken to ensure that this library returns correct withheld tax calculations and has been thoroughly checked against the ATO test data, it does not take into account certain offsets or adjustments that can be made to a person's taxation responsibility. You should always verify any calculations with a registered tax agent. AB Web Developers accepts no responsibility for any tax miscalculations or assumptions that are made as the result of using this library.
