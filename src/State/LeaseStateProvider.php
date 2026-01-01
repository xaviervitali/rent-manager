<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Lease;
use App\Repository\ImputationRepository;
use App\Services\HousingImputationsService;

/**
 * StateProvider pour enrichir les données de Lease avec les informations de loyer
 */
class LeaseStateProvider implements ProviderInterface
{
    public function __construct(
        private ItemProvider $itemProvider,
        private CollectionProvider $collectionProvider,
        private ImputationRepository $imputationRepository
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // Utiliser le bon provider selon le type d'opération
        if ($operation instanceof \ApiPlatform\Metadata\GetCollection) {
            $result = $this->collectionProvider->provide($operation, $uriVariables, $context);
        } else {
            $result = $this->itemProvider->provide($operation, $uriVariables, $context);
        }

        // Si c'est une collection
        if (is_array($result) || $result instanceof \Traversable) {
            foreach ($result as $lease) {
                if ($lease instanceof Lease) {
                    $this->enrichLeaseWithRentData($lease);
                }
            }
            return $result;
        }

        // Si c'est un seul item
        if ($result instanceof Lease) {
            $this->enrichLeaseWithRentData($result);
        }

        return $result;
    }

    /**
     * Enrichit un Lease avec les données de loyer
     */
    private function enrichLeaseWithRentData(Lease $lease): void
    {
        $housing = $lease->getHousing();

        if (!$housing) {
            return;
        }

        // Créer le service et calculer le loyer
        $service = new HousingImputationsService($housing, $this->imputationRepository);
        $rentData = $service->getCurrentRent();

        // Stocker les données dans des propriétés dynamiques
        // (accessible via les groupes de sérialisation)
        $lease->currentRent = $rentData['rent'];
        $lease->currentCharges = $rentData['charges'];
        $lease->currentTotal = $rentData['total'];
        $lease->rentDetails = $rentData['details'];

        // Calculer la somme totale des imputations actives
        $activeImputations = $this->imputationRepository->findCurrentByHousing($housing);
        $total = 0.0;
        foreach ($activeImputations as $imputation) {
            $total += (float) $imputation->getAmount();
        }
        $lease->activeImputationsTotal = $total;
    }
}