<?php
namespace ABWebDevelopers\AusIncomeTax\Source;

/**
 * Tax Table Source interface
 *
 * Represents a source for the ATO PAYG tax table information. Currently, only one source is available:
 * \ABWebDevelopers\AusIncomeTax\Source\ATOExcelSource
 *
 * @copyright 2019 AB Web Developers
 * @author Ben Thomson <ben@abweb.com.au>
 * @license MIT
 */
interface TaxTableSource
{
    /**
     * Determines the coefficients used for tax withheld calculations.
     *
     * @param integer $gross The gross income to calculate tax for, in dollars (no cents). This should be rounded down.
     * @param string $type A taxation type (ie. standard, seniors, etc.)
     * @param string $scale A taxation scale (ie. tax free threshold applied or not)
     *
     * @return array Returns an array with two values:
     *   - `percentage`: The percentage of gross income to be withheld as tax
     *   - `subtraction`: The amount to subtract from the percentage of gross
     */
    public function coefficients(
        int $gross,
        string $type = 'standard',
        string $scale = '2'
    ): array;

    /**
     * Determines the correct taxation threshold (type and scale) to use for tax calculations.
     *
     * This method provides parameters that ask a series of questions to determine the correct threshold to use. See the
     * parameter descriptions below to find which questions are asked.
     *
     * For more information on determining the answers to these questions, please see the ATO website:
     * https://www.ato.gov.au
     *
     * @param boolean $tfnProvided Has the payee provided their Tax File Number?
     * @param boolean $foreignResident Is the payee a foreign resident for tax purposes?
     * @param boolean $taxFreeThreshold Does the payee wish to claim the Tax Free Threshold on this payment?
     * @param string|null $seniorsOffset Does the payee wish to claim the Seniors Tax Offset? This should be one of the
     *  following values:
     *   - `single`: Claim the Seniors Tax Offset as a single person.
     *   - `illness-separated`: Claim the Seniors Tax Offset as a spouse that was
     *                          separated from their partner due to illness or being
     *                          interred in a nursing home.
     *   - `couple`: Claim the Seniors Tax Offset as a spouse who still lives with their
     *               partner.
     * @param string|null $medicareLevyExemption Does the payee wish to claim a Medicare Levy Exemption? This should be
     *  one of the following values:
     *   - `half`: Claim a half Medicare Levy Exemption.
     *   - `full`: Claim a full Medicare Levy Exemption.
     * @param boolean $helpDebt Does the payee currently have a HELP (Higher Education Loan Program) debt?
     * @param boolean $sfssDebt Does the payee currently have a SFSS (Student Financial Suppliment Scheme) debt?
     *
     * @return array Returns an array with two values:
     *               - `type`: The type of tax threshold to apply (ie. 'standard', 'seniors', etc.)
     *               - `scale`: The scale to apply for tax calculations based on the source
     */
    public function determineThreshold(
        bool $tfnProvided = true,
        bool $foreignResident = false,
        bool $taxFreeThreshold = true,
        ?string $seniorsOffset = null,
        ?string $medicareLevyExemption = null,
        bool $helpDebt = false,
        bool $sfssDebt = false
    ): array;

    /**
     * Validates a provide taxation threshold type and scale against the Tax Table Source.
     *
     * @param string $type The threshold type (ie. 'standard', 'seniors', etc.)
     * @param string $scale The threshold scale
     *
     * @return boolean
     */
    public function validateThreshold(
        string $type,
        string $scale
    ): bool;
}
