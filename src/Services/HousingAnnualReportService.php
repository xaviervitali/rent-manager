<?php
namespace App\Services;

use App\Repository\ImputationRepository;

final class HousingAnnualReportService
{
    public function __construct(
        private ImputationRepository $imputationRepository
    ) {}

    public function getAnnualSummary(int $year): array
    {
        $rows = $this->imputationRepository->getAnnualSummaryByHousing($year);

        $result = [];
        $totals = [
            'rent' => 0,
            'recoverableCharges' => 0,
            'nonRecoverableCharges' => 0,
            'global' => 0,
            'netIncome' => 0,
        ];

        foreach ($rows as $row) {
            $netIncome =
                $row['rentTotal'] +
                $row['recoverableChargesTotal'] -
                $row['nonRecoverableChargesTotal'];

            $result[] = [
                'housing' => [
                    'id' => $row['housingId'],
                    'title' => $row['housingTitle'],
                ],
                'rentTotal' => (float) $row['rentTotal'],
                'recoverableChargesTotal' => (float) $row['recoverableChargesTotal'],
                'nonRecoverableChargesTotal' => (float) $row['nonRecoverableChargesTotal'],
                'globalTotal' => (float) $row['globalTotal'],
                'netIncome' => (float) $netIncome,
            ];

            $totals['rent'] += $row['rentTotal'];
            $totals['recoverableCharges'] += $row['recoverableChargesTotal'];
            $totals['nonRecoverableCharges'] += $row['nonRecoverableChargesTotal'];
            $totals['global'] += $row['globalTotal'];
            $totals['netIncome'] += $netIncome;
        }

        return [
            'year' => $year,
            'byHousing' => $result,
            'totals' => $totals,
        ];
    }
}
