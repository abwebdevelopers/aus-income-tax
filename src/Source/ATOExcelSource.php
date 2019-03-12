<?php
namespace ABWebDevelopers\AusIncomeTax\Source;

use ABWebDevelopers\AusIncomeTax\Source\TaxTableSource;
use ABWebDevelopers\AusIncomeTax\Exception\SourceException;
use \PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use ABWebDevelopers\AusIncomeTax\Source\ReadFilter\ATOExcelReadFilter;

class ATOExcelSource implements TaxTableSource
{
    protected $validMedicareLevyExemptions = ['half', 'full'];
    protected $validSeniorsOffsetTypes = ['single', 'illness-separated', 'couple'];

    protected $standardSheet = 'Statement of Formula - CSV';
    protected $helpSheet = 'HELP or TSL Stat Formula - CSV';
    protected $sfssSheet = 'SFSS Stat Formula - CSV';
    protected $comboSheet = 'Combo Stat Formula - CSV';
    protected $seniorsSheet = 'Statement of Formula - CSV';

    protected $standardMatrix = [];
    protected $helpMatrix = [];
    protected $sfssMatrix = [];
    protected $comboMatrix = [];
    protected $seniorsMatrix = [];

    public function __construct($settings = [])
    {
        // Set sheet names
        if (!empty($settings['standardSheet'])) {
            $this->standardSheet = (string) $settings['standardSheet'];
        }
        if (!empty($settings['helpSheet'])) {
            $this->helpSheet = (string) $settings['helpSheet'];
        }
        if (!empty($settings['sfssSheet'])) {
            $this->sfssSheet = (string) $settings['sfssSheet'];
        }
        if (!empty($settings['comboSheet'])) {
            $this->comboSheet = (string) $settings['comboSheet'];
        }
        if (!empty($settings['seniorsSheet'])) {
            $this->seniorsSheet = (string) $settings['seniorsSheet'];
        }

        // Set source files
        if (!empty($settings['standardFile'])) {
            $this->loadStandardFile((string) $settings['standardFile']);
        }
        if (!empty($settings['helpSfssFile'])) {
            $this->loadHelpSfssFile((string) $settings['helpSfssFile']);
        }
        if (!empty($settings['seniorsFile'])) {
            $this->loadSeniorsFile((string) $settings['seniorsFile']);
        }
    }

    public function loadStandardFile($file, $sheet = null)
    {
        if ($sheet === null) {
            $sheet = $this->standardSheet;
        }

        return $this->loadCoefficients([
            'file' => $file,
            'type' => 'standard',
            'sheetName' => $sheet
        ]);
    }

    public function loadHelpSfssFile(
        $file,
        $helpSheet = null,
        $sfssSheet = null,
        $comboSheet = null
    ) {
        if ($helpSheet === null) {
            $helpSheet = $this->helpSheet;
        }
        if ($sfssSheet === null) {
            $sfssSheet = $this->sfssSheet;
        }
        if ($comboSheet === null) {
            $comboSheet = $this->comboSheet;
        }

        $this->loadCoefficients($file, 'help', $helpSheet);
        $this->loadCoefficients($file, 'sfss', $sfssSheet);
        return $this->loadCoefficients($file, 'combo', $comboSheet);
    }

    public function loadSeniorsFile($file, $sheet = null)
    {
        if ($sheet === null) {
            $sheet = $this->standardSheet;
        }

        return $this->loadCoefficients([
            'file' => $file,
            'type' => 'seniors',
            'sheetName' => $sheet
        ]);
    }

    public function coefficients(
        int $gross,
        string $type = 'standard',
        string $scale = '2'
    ): array {
        // Make sure tax table type is available
        if (!isset($this->{$type . 'Matrix'})) {
            return false;
        }

        // Make sure scale is available and is an array
        if (!isset($this->{$type . 'Matrix'}[$scale]) || !is_array($this->{$type . 'Matrix'}[$scale])) {
            return false;
        }

        // Find correct coefficients
        $percentage = false;
        $subtraction = false;
        $default = null;
        foreach ($this->{$type . 'Matrix'}[$scale] as $bracket => $values) {
            if ($bracket === 0) {
                $default = $values;
                continue;
            }

            if ($gross < $bracket) {
                $percentage = $values[0];
                $subtraction = (isset($values[1])) ? $values[1] : 0;
                break;
            }
        }

        // If the amount did not fall in defined brackets, use the default
        if ($percentage === false) {
            $percentage = $default[0];
            $subtraction = (isset($default[1])) ? $default[1] : 0;
        }

        return [
            'percentage' => $percentage,
            'subtraction' => $subtraction
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function determineThreshold(
        bool $tfnProvided = true,
        bool $foreignResident = false,
        bool $taxFreeThreshold = true,
        ?string $seniorsOffset = null,
        ?string $medicareLevyExemption = null,
        bool $helpDebt = false,
        bool $sfssDebt = false
    ): array {
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
        if (isset($seniorsOffset)) {
            if (!in_array($seniorsOffset, $this->validSeniorsOffsetTypes)) {
                throw new SourceException(
                    'Invalid seniors offset value provided, must be one of the following values: ' .
                    implode(', ', $this->validSeniorsOffsetTypes),
                    2003
                );
            }

            switch ($seniorsOffset) {
                case 'single':
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

        // Set default type and scale
        $type = 'standard';
        $scale = 1;

        // If Medicare Levy Exemption is claimed, we will always use either scale 5 (full) or scale 6 (half)
        if (isset($medicareLevyExemption)) {
            if (!in_array($medicareLevyExemption, $this->validMedicareLevyExemptions)) {
                throw new SourceException(
                    'Invalid Medicare Levy Exemption value provided, must be one of the following values: ' .
                    implode(', ', $this->validMedicareLevyExemptions),
                    2004
                );
            }

            switch ($medicareLevyExemption) {
                case 'half':
                    $scale = 6;
                    break;
                case 'full':
                    $scale = 5;
                    break;
            }
        } else {
            // If Medicare Levy Exemption is not claimed, we need to determine the scale based on claiming the tax
            // free threshold or if a foreign resident
            if ($foreignResident === true) {
                $scale = 3;
            } elseif ($taxFreeThreshold === true) {
                $scale = 2;
            } else {
                $scale = 1;
            }
        }

        // If the user has accumulated a HELP/TLS debt or is on a SFSS plan (or has both), we need to use the
        // correct type
        if ($helpDebt === true && $sfssDebt === true) {
            $type = 'combo';
        } elseif ($helpDebt === true) {
            $type = 'help';
        } elseif ($sfssDebt === true) {
            $type = 'sfss';
        }
        return [
            'type' => $type,
            'scale' => $scale
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function validateThreshold(string $type, string $scale): bool
    {
        return (isset($this->{$type . 'Matrix'}[$scale]) && is_array($this->{$type . 'Matrix'}[$scale]));
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

        if ($mime !== 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' && $mime !== 'application/octet-stream') {
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
        $reader = new XlsxReader();
        $reader->setLoadSheetsOnly($sheetName);
        $reader->setReadFilter(new ATOExcelReadFilter);
        $spreadsheet = $reader->load($file)->getActiveSheet();

        // Load rows
        foreach ($spreadsheet->getRowIterator() as $row) {
            $cells = $row->getCellIterator();
            $data = [];

            foreach ($cells as $i => $cell) {
                $value = $cell->getValue();

                // If the first value is null, skip this row
                if ($i === 'A' && $value === null) {
                    continue 2;
                }

                $data[] = $value;
            }

            // Check data row
            $this->checkRow($data);

            // Populate data
            $scale = strtolower($data[0]);
            $upperGrossLimit = $data[1];
            $multiplier = $data[2];
            $subtraction = $data[3];

            if (!isset($this->{$type . 'Matrix'}[$scale])) {
                $this->{$type . 'Matrix'}[$scale] = [];
            }
            if ($upperGrossLimit >= 999999) {
                // Set default coefficients
                $upperGrossLimit = 0;
            }
            $this->{$type . 'Matrix'}[(string) $scale][(int) $upperGrossLimit] = (empty($subtraction))
                ? [(float) $multiplier]
                : [(float) $multiplier, (float) $subtraction];
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
        if (empty($scale) || (!is_numeric($scale) && !is_string($scale))) {
            throw new SourceException('Scale must be numeric value or a string');
            return false;
        }

        // Upper gross limit must be a numeric value
        if (!is_numeric($upperGrossLimit) || (int) $upperGrossLimit < 0) {
            throw new SourceException('Upper Gross limit must be a positive numeric value');
            return false;
        }

        // Multiplier must be a float or a float formatted string
        if (preg_match('/^\s*$/', $multiplier) || (!is_float($multiplier) && (!is_string($multiplier) || (is_string($multiplier) && !preg_match('/^[0-9]+(\.[0-9]+)*$/', $multiplier)))) || (float) $multiplier < 0) {
            throw new SourceException('Multiplier must be a positive float');
            return false;
        }

        // Subtraction must be a float or a float formatted string, but can be empty
        if (!empty($subtraction) && !is_float($subtraction) && (!is_string($subtraction) || (is_string($subtraction) && !preg_match('/^[0-9]+(\.[0-9]+)*$/', $subtraction))) || (float) $subtraction < 0) {
            throw new SourceException('Subtraction value must be a positive float');
            return false;
        }

        return true;
    }
}
