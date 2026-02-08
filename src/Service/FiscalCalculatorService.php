<?php

namespace App\Service;

use App\Entity\Housing;
use App\Entity\User;
use App\Repository\AssetRepository;
use App\Repository\CreditRepository;
use App\Repository\HousingRepository;
use App\Repository\ImputationRepository;

/**
 * Service de calcul du résultat fiscal LMNP
 *
 * Résultat fiscal = Loyers - Charges déductibles - Amortissements - Intérêts d'emprunt
 */
class FiscalCalculatorService
{
    public function __construct(
        private readonly ImputationRepository $imputationRepository,
        private readonly AssetRepository $assetRepository,
        private readonly CreditRepository $creditRepository,
        private readonly HousingRepository $housingRepository,
    ) {
    }

    /**
     * Calcule le résultat fiscal pour un logement et une année donnée
     */
    public function calculateForHousing(Housing $housing, int $year): array
    {
        // 1. Récupérer les loyers (recettes)
        $revenues = $this->getRevenuesForYear($housing, $year);

        // 2. Récupérer les charges déductibles
        $deductibleCharges = $this->getDeductibleChargesForYear($housing, $year);

        // 3. Récupérer les amortissements
        $depreciations = $this->getDepreciationsForYear($housing, $year);

        // 4. Récupérer les intérêts et assurance de prêt
        $creditCharges = $this->getCreditChargesForYear($housing, $year);

        // Calcul du résultat comptable (avant amortissements)
        $accountingResult = $revenues['total'] - $deductibleCharges['total'] - $creditCharges['total'];

        // Calcul du résultat fiscal (avec amortissements)
        // Règle LMNP : les amortissements ne peuvent pas créer de déficit
        $maxDepreciation = max(0, $accountingResult);
        $usedDepreciation = min($depreciations['total'], $maxDepreciation);
        $deferredDepreciation = $depreciations['total'] - $usedDepreciation;

        $fiscalResult = $accountingResult - $usedDepreciation;

        return [
            'year' => $year,
            'housing' => [
                'id' => $housing->getId(),
                'title' => $housing->getTitle(),
            ],
            'revenues' => $revenues,
            'deductibleCharges' => $deductibleCharges,
            'creditCharges' => $creditCharges,
            'depreciations' => $depreciations,
            'accountingResult' => round($accountingResult, 2),
            'usedDepreciation' => round($usedDepreciation, 2),
            'deferredDepreciation' => round($deferredDepreciation, 2),
            'fiscalResult' => round($fiscalResult, 2),
            'isBenefit' => $fiscalResult >= 0,
            'isDeficit' => $fiscalResult < 0,
        ];
    }

    /**
     * Calcule le résultat fiscal global pour un utilisateur et une année donnée
     */
    public function calculateForUser(User $user, int $year): array
    {
        $housings = $this->housingRepository->findBy(['user' => $user]);
        $housingResults = [];

        $totalRevenues = 0;
        $totalDeductibleCharges = 0;
        $totalCreditCharges = 0;
        $totalDepreciations = 0;
        $totalUsedDepreciation = 0;
        $totalDeferredDepreciation = 0;

        foreach ($housings as $housing) {
            $result = $this->calculateForHousing($housing, $year);
            $housingResults[] = $result;

            $totalRevenues += $result['revenues']['total'];
            $totalDeductibleCharges += $result['deductibleCharges']['total'];
            $totalCreditCharges += $result['creditCharges']['total'];
            $totalDepreciations += $result['depreciations']['total'];
            $totalUsedDepreciation += $result['usedDepreciation'];
            $totalDeferredDepreciation += $result['deferredDepreciation'];
        }

        $totalAccountingResult = $totalRevenues - $totalDeductibleCharges - $totalCreditCharges;
        $totalFiscalResult = $totalAccountingResult - $totalUsedDepreciation;

        return [
            'year' => $year,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
            ],
            'housings' => $housingResults,
            'summary' => [
                'totalRevenues' => round($totalRevenues, 2),
                'totalDeductibleCharges' => round($totalDeductibleCharges, 2),
                'totalCreditCharges' => round($totalCreditCharges, 2),
                'totalDepreciations' => round($totalDepreciations, 2),
                'totalUsedDepreciation' => round($totalUsedDepreciation, 2),
                'totalDeferredDepreciation' => round($totalDeferredDepreciation, 2),
                'totalAccountingResult' => round($totalAccountingResult, 2),
                'totalFiscalResult' => round($totalFiscalResult, 2),
                'isBenefit' => $totalFiscalResult >= 0,
                'isDeficit' => $totalFiscalResult < 0,
            ],
            // Données pour la déclaration 2031/2033
            'taxForm' => $this->generateTaxFormData($housingResults, $year),
        ];
    }

    /**
     * Récupère les recettes (loyers) pour une année
     */
    private function getRevenuesForYear(Housing $housing, int $year): array
    {
        $imputations = $this->imputationRepository->findByHousingAndYear($housing, $year);
        $details = [];
        $total = 0;

        foreach ($imputations as $imputation) {
            $type = $imputation->getType();
            // Recettes = direction "credit"
            if ($type && $type->getDirection() === 'credit') {
                $amount = (float) $imputation->getAmount();
                $details[] = [
                    'id' => $imputation->getId(),
                    'type' => $type->getLabel(),
                    'amount' => $amount,
                    'date' => $imputation->getPeriodStart()->format('Y-m-d'),
                ];
                $total += $amount;
            }
        }

        return [
            'details' => $details,
            'total' => round($total, 2),
        ];
    }

    /**
     * Récupère les charges déductibles pour une année
     */
    private function getDeductibleChargesForYear(Housing $housing, int $year): array
    {
        $imputations = $this->imputationRepository->findByHousingAndYear($housing, $year);
        $details = [];
        $total = 0;

        foreach ($imputations as $imputation) {
            $type = $imputation->getType();
            // Charges déductibles = direction "debit" ET isTaxDeductible = true
            if ($type && $type->getDirection() === 'debit' && $type->isTaxDeductible()) {
                $amount = (float) $imputation->getAmount();
                $details[] = [
                    'id' => $imputation->getId(),
                    'type' => $type->getLabel(),
                    'amount' => $amount,
                    'date' => $imputation->getPeriodStart()->format('Y-m-d'),
                ];
                $total += $amount;
            }
        }

        return [
            'details' => $details,
            'total' => round($total, 2),
        ];
    }

    /**
     * Récupère les amortissements pour une année
     */
    private function getDepreciationsForYear(Housing $housing, int $year): array
    {
        $assets = $this->assetRepository->findByHousing($housing);
        $details = [];
        $total = 0;

        foreach ($assets as $asset) {
            $depreciation = $asset->getDepreciationForYear($year);
            if ($depreciation > 0) {
                $details[] = [
                    'id' => $asset->getId(),
                    'label' => $asset->getLabel(),
                    'type' => $asset->getType(),
                    'typeLabel' => $asset->getTypeLabel(),
                    'acquisitionValue' => (float) $asset->getAcquisitionValue(),
                    'depreciation' => $depreciation,
                    'duration' => $asset->getDepreciationDuration(),
                ];
                $total += $depreciation;
            }
        }

        return [
            'details' => $details,
            'total' => round($total, 2),
        ];
    }

    /**
     * Récupère les charges liées au crédit (intérêts + assurance) pour une année
     */
    private function getCreditChargesForYear(Housing $housing, int $year): array
    {
        $credits = $this->creditRepository->findBy(['housing' => $housing]);
        $details = [];
        $totalInterest = 0;
        $totalInsurance = 0;

        foreach ($credits as $credit) {
            $interest = $credit->getInterestForYear($year);
            $insurance = $credit->getInsuranceForYear($year);

            if ($interest > 0 || $insurance > 0) {
                $details[] = [
                    'id' => $credit->getId(),
                    'title' => $credit->getTitle(),
                    'interest' => $interest,
                    'insurance' => $insurance,
                    'total' => round($interest + $insurance, 2),
                ];
                $totalInterest += $interest;
                $totalInsurance += $insurance;
            }
        }

        return [
            'details' => $details,
            'totalInterest' => round($totalInterest, 2),
            'totalInsurance' => round($totalInsurance, 2),
            'total' => round($totalInterest + $totalInsurance, 2),
        ];
    }

    /**
     * Génère les données pour les formulaires fiscaux (2033-A, 2033-B, 2033-C)
     */
    private function generateTaxFormData(array $housingResults, int $year): array
    {
        $totalRevenues = 0;
        $totalDeductibleCharges = 0;
        $totalInterest = 0;
        $totalInsurance = 0;
        $totalDepreciation = 0;
        $totalUsedDepreciation = 0;

        foreach ($housingResults as $result) {
            $totalRevenues += $result['revenues']['total'];
            $totalDeductibleCharges += $result['deductibleCharges']['total'];
            $totalInterest += $result['creditCharges']['totalInterest'];
            $totalInsurance += $result['creditCharges']['totalInsurance'];
            $totalDepreciation += $result['depreciations']['total'];
            $totalUsedDepreciation += $result['usedDepreciation'];
        }

        // Données pour 2033-B (Compte de résultat)
        $form2033B = [
            // Case 218 : Autres produits (loyers)
            '218' => round($totalRevenues, 0),
            // Case 242 : Impôts, taxes (CFE, taxe foncière si dans charges déductibles)
            '242' => 0, // À calculer depuis les types de charges
            // Case 244 : Autres charges (charges déductibles hors intérêts)
            '244' => round($totalDeductibleCharges, 0),
            // Case 254 : Charges financières (intérêts + assurance prêt)
            '254' => round($totalInterest + $totalInsurance, 0),
            // Case 294 : Dotations aux amortissements
            '294' => round($totalUsedDepreciation, 0),
        ];

        // Résultat d'exploitation
        $form2033B['resultExploitation'] = $form2033B['218'] - $form2033B['242'] - $form2033B['244'] - $form2033B['254'] - $form2033B['294'];

        return [
            'form2033B' => $form2033B,
            'form2033C' => [
                // Immobilisations et amortissements
                'totalDepreciation' => round($totalDepreciation, 0),
                'usedDepreciation' => round($totalUsedDepreciation, 0),
            ],
        ];
    }

    /**
     * Récupère l'historique des résultats fiscaux sur plusieurs années
     */
    public function getHistoryForUser(User $user, int $startYear, int $endYear): array
    {
        $history = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            $result = $this->calculateForUser($user, $year);
            $history[] = [
                'year' => $year,
                'totalRevenues' => $result['summary']['totalRevenues'],
                'totalCharges' => $result['summary']['totalDeductibleCharges'] + $result['summary']['totalCreditCharges'],
                'totalDepreciation' => $result['summary']['totalUsedDepreciation'],
                'fiscalResult' => $result['summary']['totalFiscalResult'],
                'deferredDepreciation' => $result['summary']['totalDeferredDepreciation'],
            ];
        }

        return $history;
    }
}
