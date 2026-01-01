<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Housing;
use App\Entity\Imputation;
use App\Repository\ImputationRepository;

/**
 * StateProvider pour enrichir les données de Housing avec les informations de loyer
 */
class HousingStateProvider implements ProviderInterface
{
    public function __construct(
        private ItemProvider $itemProvider,
        private CollectionProvider $collectionProvider,
        private ImputationRepository $imputationRepository
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // Déterminer si c'est une collection ou un item
        $isCollection = $operation->getClass() ? 
            str_contains($operation->getName(), 'collection') : 
            false;

        // Récupérer l'entité via le provider approprié
        if ($isCollection) {
            $result = $this->collectionProvider->provide($operation, $uriVariables, $context);
        } else {
            $result = $this->itemProvider->provide($operation, $uriVariables, $context);
        }

        // Si c'est une collection
        if (is_iterable($result)) {
            foreach ($result as $housing) {
                if ($housing instanceof Housing) {
                    $this->enrichHousingWithRentData($housing);
                }
            }
            return $result;
        }

        // Si c'est un seul item
        if ($result instanceof Housing) {
            $this->enrichHousingWithRentData($result);
        }

        return $result;
    }

    /**
     * Enrichit un Housing avec les données de loyer
     */
    private function enrichHousingWithRentData(Housing $housing): void
    {
        // Récupérer la ventilation complète du loyer
        $breakdown = $this->imputationRepository->getRentBreakdown($housing);

        // Stocker les données dans des propriétés dynamiques
        $housing->currentRent = $breakdown['totals']['rent'];
        $housing->currentCharges = $breakdown['totals']['recoverableCharges'];
        $housing->currentTotal = $breakdown['totals']['total'];
        $housing->rentBreakdown = $breakdown;

        // Ajouter le bail actif s'il existe
        $housing->hasActiveLease = false;
        foreach ($housing->getLeases() as $lease) {
            if ($lease->isActive()) {
                $housing->hasActiveLease = true;
                break;
            }
        }

        // Récupérer toutes les imputations du logement avec leur statut actif
        $housing->imputationsList = $this->getImputationsWithStatus($housing);
    }

    /**
     * Récupère toutes les imputations d'un logement avec leur statut actif
     * Triées par statut actif (actifs en premier) puis par montant décroissant
     */
    private function getImputationsWithStatus(Housing $housing): array
    {
        $now = new \DateTimeImmutable();
        $imputations = $housing->getImputations()->toArray();

        $result = array_map(function (Imputation $imputation) use ($now) {
            $periodStart = $imputation->getPeriodStart();
            $periodEnd = $imputation->getPeriodEnd();
            $periodicity = $imputation->getType()->getPeriodicity();

            // Pour les imputations ponctuelles, la date de fin = date de début
            if ($periodicity === 'one_time') {
                $periodEnd = $periodStart;
            }

            // Une imputation est active si:
            // - periodStart <= aujourd'hui
            // - ET (periodEnd est null OU periodEnd >= aujourd'hui)
            $isActive = $periodStart <= $now && ($periodEnd === null || $periodEnd >= $now);

            return [
                'id' => $imputation->getId(),
                'type' => [
                    'id' => $imputation->getType()->getId(),
                    'label' => $imputation->getType()->getLabel(),
                    'isRecoverable' => $imputation->getType()->isRecoverable(),
                    'periodicity' => $periodicity,
                    'periodicityLabel' => $imputation->getType()->getPeriodicityLabel(),
                    'direction' => $imputation->getType()->getDirection(),
                    'isRentComponent' => $imputation->getType()->isRentComponent(),
                ],
                'amount' => $imputation->getAmount(),
                'periodStart' => $periodStart->format('Y-m-d'),
                'periodEnd' => $periodEnd?->format('Y-m-d'),
                'note' => $imputation->getNote(),
                'createdAt' => $imputation->getCreatedAt()->format('c'),
                'updatedAt' => $imputation->getUpdatedAt()->format('c'),
                'isActive' => $isActive,
            ];
        }, $imputations);

        // Trier: actifs en premier, puis par montant décroissant
        usort($result, function ($a, $b) {
            // D'abord par statut actif (actifs en premier)
            if ($a['isActive'] !== $b['isActive']) {
                return $b['isActive'] <=> $a['isActive'];
            }
            // Ensuite par montant décroissant
            return (float) $b['amount'] <=> (float) $a['amount'];
        });

        return $result;
    }
}