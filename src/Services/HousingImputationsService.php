<?php

namespace App\Services;

use App\Entity\Housing;
use App\Repository\ImputationRepository;

class HousingImputationsService
{
    private Housing $housing;
    private ImputationRepository $imputationRepository;

    public function __construct(Housing $housing, ImputationRepository $imputationRepository)
    {
        $this->housing = $housing;
        $this->imputationRepository = $imputationRepository;
    }

    /**
     * Retourne le loyer complet avec détails
     */
    public function getCurrentRent(?\DateTimeImmutable $date = null): array
    {
        if (!$this->housing) {
            return [
                'rent' => 0,
                'charges' => 0,
                'total' => 0,
                'details' => []
            ];
        }

        // Utiliser la méthode optimisée du repository
        $breakdown = $this->imputationRepository->getRentBreakdown($this->housing);

        return [
            'rent' => $breakdown['totals']['rent'],
            'charges' => $breakdown['totals']['recoverableCharges'],
            'total' => $breakdown['totals']['total'],
            'details' => $breakdown,
        ];
    }

    /**
     * Retourne uniquement le montant total du loyer actuel
     */
    public function getCurrentRentAmount(?\DateTimeImmutable $date = null): float
    {
        return $this->getCurrentRent($date)['total'];
    }

    /**
     * Retourne le loyer de base (hors charges)
     */
    public function getBaseRent(?\DateTimeImmutable $date = null): float
    {
        return $this->getCurrentRent($date)['rent'];
    }

    /**
     * Retourne les charges récupérables
     */
    public function getRecoverableCharges(?\DateTimeImmutable $date = null): float
    {
        return $this->getCurrentRent($date)['charges'];
    }

    /**
     * Récupère toutes les imputations actives avec détails
     */
    public function getActiveImputations(): array
    {
        return $this->imputationRepository->findCurrentByHousing($this->housing);
    }

    /**
     * Récupère les imputations pour une période donnée
     */
    public function getImputationsByPeriod(
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        ?bool $isRentComponent = null
    ): array {
        return $this->imputationRepository->findByHousingWithFilters(
            $this->housing,
            $startDate,
            $endDate,
            $isRentComponent
        );
    }

    /**
     * Formate les détails des imputations pour l'affichage
     */
    private function formatImputationDetails(array $imputations): array
    {
        $details = [
            'rentComponents' => [],    // Composantes du loyer (apparaît sur quittance)
            'recoverableCharges' => [], // Charges récupérables
            'nonRecoverableCharges' => [], // Charges non récupérables
        ];

        foreach ($imputations as $imputation) {
            $chargeType = $imputation->getType();
            
            $item = [
                'id' => $imputation->getId(),
                'label' => $chargeType->getLabel(),
                'amount' => (float) $imputation->getAmount(),
                'periodicity' => $chargeType->getPeriodicityLabel(),
                'periodicityCode' => $chargeType->getPeriodicity(),
                'periodStart' => $imputation->getPeriodStart()->format('Y-m-d'),
                'periodEnd' => $imputation->getPeriodEnd()?->format('Y-m-d'),
                'isRecoverable' => $chargeType->isRecoverable(),
                'isRentComponent' => $chargeType->isRentComponent(),
            ];

            // Catégoriser l'imputation
            if ($chargeType->isRentComponent()) {
                $details['rentComponents'][] = $item;
            } elseif ($chargeType->isRecoverable()) {
                $details['recoverableCharges'][] = $item;
            } else {
                $details['nonRecoverableCharges'][] = $item;
            }
        }

        return $details;
    }

    /**
     * Génère un récapitulatif pour une quittance
     */
    public function getReceiptSummary(): array
    {
        $currentRent = $this->getCurrentRent();
        $details = $currentRent['details'];

        return [
            'housing' => [
                'id' => $this->housing->getId(),
                'title' => $this->housing->getTitle(),
                'address' => $this->housing->getAddress(),
                'city' => $this->housing->getCity(),
                'cityCode' => $this->housing->getCityCode(),
            ],
            'rent' => [
                'base' => $currentRent['rent'],
                'charges' => $currentRent['charges'],
                'total' => $currentRent['total'],
            ],
            'breakdown' => [
                'rentComponents' => $details['rentComponents'] ?? [],
                'recoverableCharges' => $details['recoverableCharges'] ?? [],
            ],
            'generatedAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Calcule le total des charges non récupérables (pour info propriétaire)
     */
    public function getNonRecoverableChargesTotal(): float
    {
        return $this->imputationRepository->calculateNonRecoverableCharges($this->housing);
    }

    /**
     * Vérifie si le logement a des imputations actives
     */
    public function hasActiveImputations(): bool
    {
        return count($this->getActiveImputations()) > 0;
    }

    /**
     * Retourne le revenu net du propriétaire (loyer - charges non récupérables)
     */
    public function getNetIncome(): float
    {
        $totalRent = $this->getCurrentRentAmount();
        $nonRecoverableCharges = $this->getNonRecoverableChargesTotal();
        
        return $totalRent - $nonRecoverableCharges;
    }

    
}