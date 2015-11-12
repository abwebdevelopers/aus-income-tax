<?php
namespace ABWeb\IncomeTax\Source;

use ABWeb\IncomeTax\Source\TaxTableSource;
use ABWeb\IncomeTax\Exception\SourceException;

class ATOExcelSource implements TaxTableSource
{
    protected $validMedicareLevyExemptions = ['none', 'half', 'full'];
    protected $validSeniorsOffsetTypes = ['single', 'illness-separated', 'couple'];

    protected $standardMatrix = [];
    protected $helpMatrix = [];
    protected $sfssMatrix = [];
    protected $comboMatrix = [];
    protected $seniorsMatrix = [];

    public function __construct($settings = [])
    {
        if (!empty($settings['standardFile'])) {
            $this->loadStandardFile($settings['standardFile']);
        }
        if (!empty($settings['helpSfssFile'])) {
            $this->loadHelpSfssFile($settings['helpSfssFile']);
        }
        if (!empty($settings['seniorsFile'])) {
            $this->loadSeniorsFile($settings['seniorsFile']);
        }
    }

    public function loadStandardFile($file)
    {
        return $this->loadCoefficients($file, 'standard', 'Statement of Formula - CSV');
    }

    public function loadHelpSfssFile($file)
    {
        $this->loadCoefficients($file, 'help', 'HELP or TSL Stat Formula - CSV');
        $this->loadCoefficients($file, 'sfss', 'SFSS Stat Formula - CSV');
        $this->loadCoefficients($file, 'combo', 'Combo Stat Formula - CSV');
    }

    public function loadSeniorsFile($file)
    {
        return $this->loadCoefficients($file, 'seniors', 'Statement of Formula - CSV');
    }

    public function coefficients(
        $amountBeforeTax = null,
        $type = 'standard',
        $scale = 2
    ) {
        if (is_array($amountBeforeTax)) {
            extract($amountBeforeTax);
        }
    }

    public function determineScale(
        $tfnProvided = true,
        $foreignResident = false,
        $taxFreeThreshold = true,
        $seniorsOffset = false,
        $seniorsOffsetType = null,
        $medicareLevyExemption = 'none',
        $helpDebt = false,
        $sfssDebt = false
    ) {
        if (is_array($tfnProvided)) {
            extract($tfnProvided);
        }

        // If tax file number is not provided, they automatically must use the standard "no TFN" scale
        if ($tfnProvided === false) {
            if ($foreignResident === true) {
                return [
                    'type' => 'standard',
                    'scale' => '4 non resident'
                ];
            } else {
                return [
                    'type' => 'standard',
                    'scale' => '4 resident'
                ];
            }
        }

        // If seniors offset is claimed, we must use the seniors offset scales
        if ($seniorsOffset === true) {
            $seniorsOffsetType = (in_array(strtolower($seniorsOffsetType), $this->validSeniorsOffsetTypes)) ? strtolower($seniorsOffsetType) : 'single';

            switch ($seniorsOffsetType) {
                case 'single':
                default:
                    return [
                        'type' => 'seniors',
                        'scale' => 'single'
                    ];
                    break;
                case 'illness-separated':
                    return [
                        'type' => 'seniors',
                        'scale' => 'illness-separated'
                    ];
                    break;
                case 'couple':
                    return [
                        'type' => 'seniors',
                        'scale' => 'member of a couple'
                    ];
                    break;
            }
        }

        // If Medicare Levy Exemption is claimed, we will always use either scale 5 (full) or scale 6 (half)
        $type = 'standard';
        $scale = 1;

        $medicareLevyExemption = (in_array(strtolower($medicareLevyExemption), $this->validMedicareLevyExemptions)) ? strtolower($medicareLevyExemption) : 'none';

        switch ($medicareLevyExemption) {
            case 'half':
                $scale = 6;
                break;
            case 'full':
                $scale = 5;
                break;
            case 'none':
            default:
                break;
        }

        // If the user has accumulated a HELP/TLS debt or is on a SFSS plan (or has both), we need to use the correct type
        if ($helpDebt === true && $sfssDebt === true) {
            $type = 'combo';
        } elseif ($helpDebt === true) {
            $type = 'help';
        } elseif ($sfssDebt === true) {
            $type = 'sfss';
        }

        // Finally, we need to determine the scale based on claiming the tax free threshold or if a foreign resident
        if ($medicareLevyExemption === 'none') {
            if ($foreignResident === true) {
                $scale = 3;
            } elseif ($taxFreeThreshold === true) {
                $scale = 2;
            } else {
                $scale = 1;
            }
        }

        return [
            'type' => $type,
            'scale' => $scale
        ];
    }

    private function isValidFile($file)
    {
        // Check that file exists
        if (!file_exists($file) || !is_file($file)) {
            throw new SourceException('File &quot;' . $file . '&quot; does not exist.', 31250);
            return false;
        }

        // Check that the file is an XLSX format file
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file);

        if ($mime !== 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
            throw new SourceException('File &quot;' . $file . '&quot; is not a valid XLSX file.', 31251);
            return false;
        }

        return true;
    }

    private function loadCoefficients($file, $type = 'standard', $sheetName = null)
    {
        if (is_array($file)) {
            extract($file);
        }

        // Check file
        if ($this->isValidFile($file) === false) {
            return false;
        }

        // Erase current values in type
        $this->{$type . 'Matrix'} = [];

        // Initiate reader
        $reader = new \SpreadsheetReader($file);
        $sheets = $reader->Sheets();
        $sheets = array_map('strtolower', $sheets);

        // Use correct sheet
        if ($sheetName === null || count($sheets) === 1 || !in_array(strtolower($sheetName), $sheets)) {
            // Use the first sheet
            $reader->changeSheet(0);
        } else {
            $key = array_search(strtolower($sheetName), $sheets);
            $reader->changeSheet($key);
        }

        // Load rows
        $empty = 0;
        $validRow = false;
        foreach ($reader as $i => $row) {
            // Skip headers
            if ($i === 0) {
                continue;
            }

            // Skip empty rows - if we encounter 3 in a row, break the loop
            if (trim($row[0]) == '') {
                ++$empty;
                continue;
            } else {
                $empty = 0;
            }
            if ($empty === 3) {
                break;
            }

            // Check data row
            try {
                $this->checkRow($row);
            } catch (SourceException $e) {
                continue;
            }
            $validRow = true;

            // Populate data
            $scale = strtolower($row[0]);
            $upperGrossLimit = $row[1];
            $multiplier = $row[2];
            $subtraction = $row[3];

            if (!isset($this->{$type . 'Matrix'}[$scale])) {
                $this->{$type . 'Matrix'}[$scale] = [];
            }
            if ($upperGrossLimit >= 999999) {
                // Set default coefficients
                $upperGrossLimit = 0;
            }
            $this->{$type . 'Matrix'}[(string) $scale][(int) $upperGrossLimit] = (empty($subtraction)) ? [(float) $multiplier] : [(float) $multiplier, (float) $subtraction];
        }

        if ($validRow === false) {
            throw new SourceException('Did not find a valid coefficients row from source', 31253);
            return false;
        }

        return true;
    }

    private function checkRow($row)
    {
        $scale = $row[0];
        $upperGrossLimit = $row[1];
        $multiplier = $row[2];
        $subtraction = $row[3];

        // Scale must be numeric or a string
        if (!is_numeric($scale) && !is_string($scale)) {
            throw new SourceException('Scale must be numeric value or a string');
            return false;
        }

        // Upper gross limit must be a numeric value
        if (!is_numeric($upperGrossLimit)) {
            throw new SourceException('Upper Gross limit must be a numeric value');
            return false;
        }

        // Multiplier must be a float or a float formatted string
        if (!is_string($multiplier) && !is_float($multiplier) && (is_string($multiplier) && !preg_match('/^[0-9]+\.[0-9]+$/', $multiplier))) {
            throw new SourceException('Multiplier must be a float');
            return false;
        }

        // Subtraction must be a float or a float formatted string, but can be empty
        if (!empty($subtraction) && !is_string($subtraction) && !is_float($subtraction) && (is_string($subtraction) && !preg_match('/^[0-9]+\.[0-9]+$/', $subtraction))) {
            throw new SourceException('Subtraction value must be a float');
            return false;
        }

        return true;
    }
}
