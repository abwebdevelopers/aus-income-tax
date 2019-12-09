<?php
namespace ABWebDevelopers\AusIncomeTax\Source;

use ABWebDevelopers\AusIncomeTax\Source\TaxTableSource;
use ABWebDevelopers\AusIncomeTax\Exception\SourceException;
use \PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use ABWebDevelopers\AusIncomeTax\Source\ReadFilter\ATOExcelReadFilter;

/**
 * ATO Excel Spreadsheet Source
 *
 * This source reads the Excel spreadsheets that are provided by the Australian Tax Office for calculating the withheld
 * portion of gross income. The following documents are applicable:
 *
 * - NAT 1004: Standard formula for working out income tax.
 * - NAT 3539: Formula for working out income tax for people who claim a HELP (Higher Education Loan Program),
 *   SFSS (Student Financial Supplement Scheme) or other student assistance debt.
 * - NAT 4466: Formula for working out income tax for seniors and pensioners.
 *
 * For more details on usage, please review the README in the root of this library.
 *
 * @copyright 2019 AB Web Developers
 * @author Ben Thomson <ben@abweb.com.au>
 * @license MIT
 */
class ATOExcelSource implements TaxTableSource
{
    /** @var array Valid Medicare Levy exemption types */
    protected $medicareLevyExemptions = [
        'half',
        'full'
    ];

    /** @var array Valid Seniors Offset types */
    protected $seniorsOffsetTypes = [
        'single',
        'illness-separated',
        'couple'
    ];

    /** @var string The name of the worksheet to retrieve standard coefficient data from */
    protected $standardSheet = 'Statement of Formula - CSV';

    /** @var string The name of the worksheet to retrieve HELP/TSL coefficient data from */
    protected $helpSheet = 'HELP or TSL Stat Formula - CSV';

    /** @var string The name of the worksheet to retrieve SFSS coefficient data from */
    protected $sfssSheet = 'SFSS Stat Formula - CSV';

    /** @var string The name of the worksheet to retrieve HELP/TSL/SFSS combination coefficient data from */
    protected $comboSheet = 'Combo Stat Formula - CSV';

    /** @var string The name of the worksheet to retrieve seniors coefficient data from */
    protected $seniorsSheet = 'Statement of Formula - CSV';

    /** @var array Cached standard coefficient data */
    protected $standardMatrix = [];
    /** @var array Cached HELP/TSL coefficient data */
    protected $helpMatrix = [];
    /** @var array Cached SFSS coefficient data */
    protected $sfssMatrix = [];
    /** @var array Cached HELP/TSL/SFSS combination coefficient data */
    protected $comboMatrix = [];
    /** @var array Cached seniors coefficient data */
    protected $seniorsMatrix = [];

    /**
     * Constructor.
     *
     * Allows for the definition of settings to use throughout the loading of the data from the Excel spreadsheets.
     *
     * @param array $settings
     */
    public function __construct(array $settings = [])
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

    /**
     * Loads the NAT 1004 Excel spreadsheet
     *
     * @param string $file
     * @param string $sheet
     * @return self Fluent interface
     */
    public function loadStandardFile(string $file, string $sheet = null)
    {
        if ($sheet === null) {
            $sheet = $this->standardSheet;
        }

        $this->loadCoefficients($file, 'standard', $sheet);

        return $this;
    }

    /**
     * Loads the NAT 3539 Excel spreadsheet
     *
     * @param string $file
     * @param string|null $helpSheet The worksheet that contains the HELP CSV data
     * @param string|null $sfssSheet The worksheet that contains the SFSS CSV data
     * @param string|null $comboSheet The worksheet that contains the combo CSV data
     * @return self Fluent interface
     */
    public function loadHelpSfssFile(
        string $file,
        string $helpSheet = null,
        string $sfssSheet = null,
        string $comboSheet = null
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
        $this->loadCoefficients($file, 'combo', $comboSheet);

        return $this;
    }

    /**
     * Loads the NAT 4466 Excel spreadsheet
     *
     * @param string $file
     * @param string $sheet
     * @return self Fluent interface
     */
    public function loadSeniorsFile(string $file, string $sheet = null)
    {
        if ($sheet === null) {
            $sheet = $this->standardSheet;
        }

        $this->loadCoefficients($file, 'seniors', $sheet);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function coefficients(
        int $gross,
        string $type = 'standard',
        string $scale = '2'
    ): array {
        // Make sure scale is available and is an array
        if (!$this->validateThreshold($type, $scale)) {
            throw new SourceException('Invalid threshold type or scale provided', 2001);
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
            if (!in_array($seniorsOffset, $this->seniorsOffsetTypes)) {
                throw new SourceException(
                    'Invalid seniors offset value provided, must be one of the following values: ' .
                    implode(', ', $this->seniorsOffsetTypes),
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
            if (!in_array($medicareLevyExemption, $this->medicareLevyExemptions)) {
                throw new SourceException(
                    'Invalid Medicare Levy Exemption value provided, must be one of the following values: ' .
                    implode(', ', $this->medicareLevyExemptions),
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

    /**
     * Loads the coefficient data from the Excel spreadsheet.
     *
     * @param string $file
     * @param string $type
     * @param string $sheetName
     * @return void
     */
    protected function loadCoefficients(string $file, string $type = 'standard', string $sheetName = null): void
    {
        // Check file
        $this->checkValidFile($file);

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
    }

    /**
     * Checks if the file is an Excel spreadsheet.
     *
     * @param string $file
     * @throws SourceException If the file is not a vaild Excel spreadsheet.
     * @return void
     */
    protected function checkValidFile(string $file): void
    {
        // Check that file exists
        if (!file_exists($file) || !is_file($file)) {
            throw new SourceException('File &quot;' . $file . '&quot; does not exist.', 2002);
        }

        // Check that the file is an XLSX format file
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file);

        if (
            $mime !== 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            && $mime !== 'application/octet-stream'
        ) {
            throw new SourceException('File &quot;' . $file . '&quot; is not a valid XLSX file.', 2002);
        }
    }

    /**
     * Checks if a row in the spreadsheet looks like valid coefficient data.
     *
     * A valid row is defined as 4 colum,s (array items) with the following information:
     *  0: Integer or string value as the scale
     *  1: Integer value as an upper gross limit
     *  2: Multiplier as a float value
     *  3: Subtraction as an (optional) float value
     *
     * @param array $row
     * @throws SourceException If a value provided in the row does not match the row definition above.
     * @return void
     */
    protected function checkRow(array $row): void
    {
        $scale = $row[0];
        $upperGrossLimit = $row[1];
        $multiplier = $row[2];
        $subtraction = $row[3];

        // Regular expressions
        $intRegex = '/^[0-9]+$/';
        $floatRegex = '/^[0-9]+(\.[0-9]+)*$/';

        // Scale must be a positive integer or a string
        if (
            empty($scale)
            || (!preg_match($intRegex, $scale) && !is_string($scale))
            || (preg_match($intRegex, $scale) && (int) $scale < 1)
        ) {
            throw new SourceException('Scale must be a positive integer or a string', 2005);
        }

        // Upper gross limit must be an unsigned integer
        if (
            !preg_match($intRegex, $upperGrossLimit)
            || (int) $upperGrossLimit < 0
        ) {
            throw new SourceException('Upper gross limit must be an unsigned integer', 2005);
        }

        // Multiplier must be a float between 0 and 1
        if (
            !preg_match($floatRegex, $multiplier)
            || (
                (float) $multiplier < 0
                || (float) $multiplier > 1
            )
        ) {
            throw new SourceException('Multiplier must be a float between 0 and 1', 2005);
        }

        // Subtraction must be an unsigned float, but can be empty
        if (
            !empty($subtraction)
            && (
                !preg_match($floatRegex, $multiplier)
                || (float) $subtraction < 0
            )
        ) {
            throw new SourceException('Subtraction must be an unsigned float', 2005);
        }
    }
}
