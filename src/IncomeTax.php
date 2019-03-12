<?php
namespace ABWebDevelopers\AusIncomeTax;

use ABWebDevelopers\AusIncomeTax\Source\TaxTableSource;
use ABWebDevelopers\AusIncomeTax\Exception\SourceException;
use ABWebDevelopers\AusIncomeTax\Exception\CalculationException;

/**
 * Income Tax class.
 *
 * Processes and calculates the tax withheld amount of a given gross income amount according to the rules of the
 * Australian Tax Office.
 *
 * @copyright 2019 AB Web Developers
 * @author Ben Thomson <ben@abweb.com.au>
 * @license MIT
 */
class IncomeTax
{
    /**
     * The source to read tax calculation coefficients from.
     *
     * @var \ABWebDevelopers\AusIncomeTax\Source\TaxTableSource
     */
    protected $source = null;

    /**
     * A list of valid payment frequency values.
     *
     * @var array
     */
    protected $validFrequencies = [
        'weekly',
        'fortnightly',
        'monthly',
        'quarterly'
    ];

    public function __construct(TaxTableSource $source = null)
    {
        if (isset($source)) {
            $this->loadSource($source);
        }
    }

    /**
     * Loads a valid tax table coefficient source.
     *
     * @param TaxTableSource $source
     * @return IncomeTax
     */
    public function loadSource(TaxTableSource $source): IncomeTax
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Calculates the tax withheld amount for a given gross income.
     *
     * @param integer $gross The gross income to calculate tax for, in dollars (no cents). This should be rounded down.
     * @param string $frequency The payment frequency. Must be a value provided by `$validFrequencies`.
     * @param \DateTimeInterface $date The payment date. Tax can be affected by leap years, so the date must be provided
     *                                 to check for this instance. If not provided, it will default to today.
     * @param array $threshold The tax threshold to apply. The array should contain two values:
     *                         - `type`: A taxation type (ie. standard, seniors, etc.)
     *                         - `scale`: A taxation scale (ie. tax free threshold applied or not)
     *                         Alternatively, you may provide the parameters for `determineScale()` in an array to have
     *                         the library automatically determine the correct type and scale.
     *
     * @return int The amount to withhold for tax, in dollars (no cents).
     */
    public function calculateTax(
        int $gross = 0,
        string $frequency = 'weekly',
        \DateTimeInterface $date = null,
        array $threshold = null
    ) {
        if (!isset($this->source)) {
            throw new SourceException('You must specify a Tax Table Source.');
            return false;
        }

        // Check parameters
        if ($gross < 0) {
            throw new CalculationException('Gross income amount cannot be negative', 2001);
        }
        if (!in_array($frequency, $this->validFrequencies)) {
            throw new CalculationException(
                'Invalid payment frequency specified - must be one of the following value: ' .
                implode(', ', $this->validFrequencies),
                2002
            );
            return false;
        }
        if (!isset($threshold['type']) && !isset($threshold['scale'])) {
            // Check to see if this is an array to pass on to `determineScale()`
            $parameters = [
                'tfnProvided',
                'foreignResident',
                'taxFreeThreshold',
                'seniorsOffset',
                'medicareLevyExemption',
                'helpDebt',
                'sfssDebt'
            ];
            $diff = array_diff($parameters, $threshold);

            if (count($diff)) {
                throw new CalculationException(
                    'Missing parameters for automatically determining scale: ' .
                    implode(', ', $diff),
                    2001
                );
            }

            $threshold = $this->determineThreshold(
                $threshold['tfnProvided'],
                $threshold['foreignResident'],
                $threshold['taxFreeThreshold'],
                $threshold['seniorsOffset'],
                $threshold['medicareLevyExemption'],
                $threshold['helpDebt'],
                $threshold['sfssDebt']
            );
        }

        // Validate threshold
        if (!$this->validateThreshold($threshold['type'], $threshold['scale'])) {
            throw new CalculationException(
                'Invalid tax threshold provided',
                2001
            );
        }

        // If date is not provided, default to today's date
        if (!isset($date)) {
            $date = new \DateTime;
        }

        // Calculate tax
        switch ($frequency) {
            case 'weekly':
            default:
                return $this->calculateWeeklyTax($gross, $date, $threshold['type'], $threshold['scale']);
                break;
            case 'fortnightly':
                return $this->calculateFortnightlyTax($gross, $date, $threshold['type'], $threshold['scale']);
                break;
            case 'monthly':
                return $this->calculateMonthlyTax($gross, $date, $threshold['type'], $threshold['scale']);
                break;
        }
    }

    /**
     * Automatically determines the tax threshold as per the source.
     *
     * @see \ABWebDevelopers\AusIncomeTax\Source\TaxTableSource::determineThreshold();
     */
    protected function determineThreshold(
        bool $tfnProvided = true,
        bool $foreignResident = false,
        bool $taxFreeThreshold = true,
        ?string $seniorsOffset = null,
        ?string $medicareLevyExemption = null,
        bool $helpDebt = false,
        bool $sfssDebt = false
    ): array {
        return $this->source->determineThreshold(
            $tfnProvided,
            $foreignResident,
            $taxFreeThreshold,
            $seniorsOffset,
            $medicareLevyExemption,
            $helpDebt,
            $sfssDebt
        );
    }

    /**
     * Validates the tax threshold as per the source.
     *
     * @see \ABWebDevelopers\AusIncomeTax\Source\TaxTableSource::validateThreshold();
     */
    protected function validateThreshold(
        string $type,
        string $scale
    ): bool {
        return $this->source->validateThreshold($type, $scale);
    }

    /**
     * Calculates tax withheld based on a weekly pay cycle.
     *
     * @param integer $gross
     * @param \DateTimeInterface $date
     * @param string $type
     * @param string $scale
     *
     * @return integer
     */
    protected function calculateWeeklyTax(int $gross, \DateTimeInterface $date, string $type, string $scale): int
    {
        if ($scale === '4 resident' || $scale === '4 non resident') {
            // Scale 4 earnings have all cents ignored
            $earnings = floor($gross);
        } else {
            // Round to nearest dollar and add 99 cents
            $earnings = round($gross, 0, PHP_ROUND_HALF_UP) + 0.99;
        }

        // Retrieve coefficients
        $coefficients = $this->source->coefficients($earnings, $type, $scale);
        extract($coefficients);

        // Calculate tax
        if ($percentage === 0) {
            return 0;
        }

        $tax = ($earnings * $percentage) - $subtraction;

        if ($scale === '4 resident' || $scale === '4 non resident') {
            // When scale 4 is used, any cents in the tax must be ignored
            $tax = floor($tax);
        } else {
            $tax = round($tax, 0, PHP_ROUND_HALF_UP);
        }

        // If it's a leap year, add additional withholding
        if (intval($date->format('Y')) % 4 === 0) {
            if ($earnings >= 3450) {
                $tax += 10;
            } else if ($earnings >= 1525) {
                $tax += 4;
            } else if ($earnings >= 725) {
                $tax += 3;
            }
        }

        return $tax;
    }

    /**
     * Calculates tax withheld based on a fortnightly pay cycle.
     *
     * @param integer $gross
     * @param \DateTimeInterface $date
     * @param string $type
     * @param string $scale
     *
     * @return integer
     */
    protected function calculateFortnightlyTax(int $gross, \DateTimeInterface $date, string $type, string $scale): int
    {
        if ($scale === '4 resident' || $scale === '4 non resident') {
            // Scale 4 earnings have all cents ignored
            $earnings = floor($gross / 2);
        } else {
            // Divide fortnightly income by two, ignoring cents, then add 99 cents
            $earnings = floor($gross / 2) + 0.99;
        }

        // Retrieve coefficients
        $coefficients = $this->source->coefficients($earnings, $type, $scale);
        extract($coefficients);

        // Calculate tax
        if ($percentage === 0) {
            return 0;
        }

        $tax = ($earnings * $percentage) - $subtraction;

        if ($scale === '4 resident' || $scale === '4 non resident') {
            // When scale 4 is used, any cents in the tax must be ignored
            $tax = floor($tax);
        } else {
            $tax = round($tax, 0, PHP_ROUND_HALF_UP);
        }

        // Fortnightly tax is weekly tax doubled
        $tax *= 2;

        // If it's a leap year, add additional withholding
        if (intval($date->format('Y')) % 4 === 0) {
            if ($earnings >= 6800) {
                $tax += 42;
            } else if ($earnings >= 3050) {
                $tax += 17;
            } else if ($earnings >= 1400) {
                $tax += 12;
            }
        }

        return $tax;
    }

    /**
     * Calculates tax withheld based on a monthly pay cycle.
     *
     * @param integer $gross
     * @param \DateTimeInterface $date
     * @param string $type
     * @param string $scale
     *
     * @return integer
     */
    protected function calculateMonthlyTax(int $gross, \DateTimeInterface $date, string $type, string $scale): int
    {
        // If a monthly payment ends in 33 cents, it needs to be bumped up to 34
        if ($gross - floor($gross) == 0.33) {
            $gross += 0.01;
        }

        // Then, we need to multiply this by 3, then divide by 13
        $gross = ($gross * 3) / 13;

        if ($scale === '4 resident' || $scale === '4 non resident') {
            // Scale 4 earnings have all cents ignored
            $earnings = floor($gross);
        } else {
            // Ignore cents value, add 99 cents
            $earnings = floor($gross) + 0.99;
        }

        // Retrieve coefficients
        $coefficients = $this->source->coefficients($earnings, $type, $scale);
        extract($coefficients);

        // Calculate tax
        if ($percentage === 0) {
            return 0;
        }

        $tax = ($earnings * $percentage) - $subtraction;

        if ($scale === '4 resident' || $scale === '4 non resident') {
            // When scale 4 is used, any cents in the tax must be ignored
            $tax = floor($tax);
        } else {
            $tax = round($tax, 0, PHP_ROUND_HALF_UP);
        }

        // Adjust back into a monthly tax value
        $tax = ($tax * 13) / 3;

        return round($tax, 0, PHP_ROUND_HALF_UP);
    }
}
