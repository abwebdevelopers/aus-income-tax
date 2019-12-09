<?php
namespace ABWebDevelopers\AusIncomeTax\Source\ReadFilter;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * Filter to read specific columns and rows for coefficient data.
 *
 * @copyright 2019 AB Web Developers
 * @author Ben Thomson <ben@abweb.com.au>
 * @license MIT
 */
class ATOExcelReadFilter implements IReadFilter
{
    public function readCell($column, $row, $worksheetName = '')
    {
        return in_array($row, range(2, 39)) && in_array($column, range('A', 'D'));
    }
}
