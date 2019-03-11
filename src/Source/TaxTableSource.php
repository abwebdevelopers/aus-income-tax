<?php
namespace ABWebDevelopers\AusIncomeTax\Source;

interface TaxTableSource
{
    public function coefficients(
        $amountBeforeTax = null,
        $type = 'standard',
        $scale = 2
    );

    public function determineScale(
        $tfnProvided = true,
        $foreignResident = false,
        $taxFreeThreshold = true,
        $seniorsOffset = false,
        $medicareLevyExemption = false,
        $helpDebt = false,
        $sfssDebt = false
    );
}
