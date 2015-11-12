<?php
namespace ABWeb\IncomeTax\Source;

interface TaxTableSource
{
    public function coefficients(
        $amountBeforeTax = null,
        $scale = 2,
        $claimedHelp = false,
        $claimedSfss = false,
        $seniorOffset = false,
        $seniorOffsetType = null,
        $medicareAdjustment = null,
        $variation = null
    );

    public function determineScale(
        $tfnProvided = true,
        $taxFreeThreshold = true,
        $foreignResident = false,
        $medicareLevyExcemption = 'none'
    );
}
