<?php
namespace ABWebDevelopers\AusIncomeTax\Source\ReadFilter;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ATOExcelReadFilter implements IReadFilter
{
    public function readCell($column, $row, $worksheetName = '')
    {
        return in_array($row, range(2, 39)) && in_array($column, range('A', 'D'));
    }
}
