<?php

namespace App\Repository;

use App\Entity\Asset;
use App\Entity\Housing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Asset>
 */
class AssetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Asset::class);
    }

    /**
     * Trouve toutes les immobilisations d'un logement
     * @return Asset[]
     */
    public function findByHousing(Housing $housing): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.housing = :housing')
            ->setParameter('housing', $housing)
            ->orderBy('a.acquisitionDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les immobilisations actives (non entièrement amorties) à une date donnée
     * @return Asset[]
     */
    public function findActiveByHousing(Housing $housing, \DateTimeInterface $date = null): array
    {
        $date = $date ?? new \DateTime();
        $assets = $this->findByHousing($housing);

        return array_filter($assets, fn(Asset $asset) => !$asset->isFullyDepreciated($date));
    }

    /**
     * Calcule le total des amortissements pour une année donnée pour un logement
     */
    public function getTotalDepreciationForYear(Housing $housing, int $year): float
    {
        $assets = $this->findByHousing($housing);
        $total = 0;

        foreach ($assets as $asset) {
            $total += $asset->getDepreciationForYear($year);
        }

        return round($total, 2);
    }

    /**
     * Calcule le total des amortissements pour une année donnée pour tous les logements d'un utilisateur
     */
    public function getTotalDepreciationForYearByUser(int $userId, int $year): float
    {
        $assets = $this->createQueryBuilder('a')
            ->join('a.housing', 'h')
            ->andWhere('h.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();

        $total = 0;
        foreach ($assets as $asset) {
            $total += $asset->getDepreciationForYear($year);
        }

        return round($total, 2);
    }

    /**
     * Retourne le tableau d'amortissement détaillé par année pour un logement
     */
    public function getDepreciationSchedule(Housing $housing): array
    {
        $assets = $this->findByHousing($housing);

        if (empty($assets)) {
            return [];
        }

        // Trouver la plage d'années
        $minYear = PHP_INT_MAX;
        $maxYear = 0;

        foreach ($assets as $asset) {
            $acquisitionYear = (int) $asset->getAcquisitionDate()->format('Y');
            $endYear = $acquisitionYear + $asset->getDepreciationDuration();

            $minYear = min($minYear, $acquisitionYear);
            $maxYear = max($maxYear, $endYear);
        }

        $schedule = [];
        for ($year = $minYear; $year <= $maxYear; $year++) {
            $yearData = [
                'year' => $year,
                'assets' => [],
                'total' => 0,
            ];

            foreach ($assets as $asset) {
                $depreciation = $asset->getDepreciationForYear($year);
                if ($depreciation > 0) {
                    $yearData['assets'][] = [
                        'id' => $asset->getId(),
                        'label' => $asset->getLabel(),
                        'type' => $asset->getType(),
                        'depreciation' => $depreciation,
                    ];
                    $yearData['total'] += $depreciation;
                }
            }

            $yearData['total'] = round($yearData['total'], 2);
            $schedule[] = $yearData;
        }

        return $schedule;
    }
}
