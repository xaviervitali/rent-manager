<?php

namespace App\Repository;

use App\Entity\Housing;
use App\Entity\Imputation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Imputation>
 */
class ImputationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Imputation::class);
    }
 
    /**
     * Récupère les imputations actives d'un logement
     */
    public function findCurrentByHousing(Housing $housing): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('i')
            ->andWhere('i.housing = :housing')
            ->andWhere('i.periodStart <= :now')
            ->andWhere('i.periodEnd IS NULL OR i.periodEnd >= :now')
            ->setParameter('housing', $housing)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Calcule le loyer total d'un logement (composantes du loyer sur la quittance)
     */
    public function calculateRent(Housing $housing): float
    {
        $result = $this->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.amount), 0) as total')
            ->join('i.type', 't')
            ->andWhere('i.housing = :housing')
            ->andWhere('t.isRentComponent = :true')
            ->andWhere('i.periodStart <= :now')
            ->andWhere('i.periodEnd IS NULL OR i.periodEnd >= :now')
            ->setParameter('housing', $housing)
            ->setParameter('true', true)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();
        
        return (float) ($result ?? 0.0);
    }
    
    /**
     * Calcule les charges récupérables d'un logement
     */
    public function calculateCharges(Housing $housing): float
    {
        $result = $this->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.amount), 0) as total')
            ->join('i.type', 't')
            ->andWhere('i.housing = :housing')
            ->andWhere('t.isRecoverable = :true')
            ->andWhere('i.periodStart <= :now')
            ->andWhere('i.periodEnd IS NULL OR i.periodEnd >= :now')
            ->setParameter('housing', $housing)
            ->setParameter('true', true)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();
        
        return (float) ($result ?? 0.0);
    }

    /**
     * Calcule le total des charges non récupérables
     */
    public function calculateNonRecoverableCharges(Housing $housing): float
    {
        $result = $this->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.amount), 0) as total')
            ->join('i.type', 't')
            ->andWhere('i.housing = :housing')
            ->andWhere('t.isRecoverable = :false')
            ->andWhere('t.isRentComponent = :false')
            ->andWhere('i.periodStart <= :now')
            ->andWhere('i.periodEnd IS NULL OR i.periodEnd >= :now')
            ->setParameter('housing', $housing)
            ->setParameter('false', false)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();
        
        return (float) ($result ?? 0.0);
    }

    /**
     * Recherche d'imputations avec filtres
     */
    public function findByHousingWithFilters(
        Housing $housing, 
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        ?bool $isRentComponent = null
    ): array {
        $qb = $this->createQueryBuilder('i')
            ->andWhere('i.housing = :housing')
            ->setParameter('housing', $housing);
        
        if ($startDate) {
            $qb->andWhere('i.periodStart >= :startDate')
               ->setParameter('startDate', $startDate);
        }
        
        if ($endDate) {
            $qb->andWhere('i.periodEnd <= :endDate OR i.periodEnd IS NULL')
               ->setParameter('endDate', $endDate);
        }
        
        if ($isRentComponent !== null) {
            $qb->join('i.type', 't')
               ->andWhere('t.isRentComponent = :isRentComponent')
               ->setParameter('isRentComponent', $isRentComponent);
        }
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les détails ventilés des imputations actives
     */
    public function getRentBreakdown(Housing $housing): array
    {
        $imputations = $this->findCurrentByHousing($housing);
        
        $breakdown = [
            'rentComponents' => [],
            'recoverableCharges' => [],
            'nonRecoverableCharges' => [],
            'totals' => [
                'rent' => 0,
                'recoverableCharges' => 0,
                'nonRecoverableCharges' => 0,
                'total' => 0,
            ]
        ];

        foreach ($imputations as $imputation) {
            $chargeType = $imputation->getType();
            $amount = (float) $imputation->getAmount();
            
            $item = [
                'id' => $imputation->getId(),
                'label' => $chargeType->getLabel(),
                'amount' => $amount,
                'periodicity' => $chargeType->getPeriodicity(),
                'periodicityLabel' => $chargeType->getPeriodicityLabel(),
                'direction' =>$chargeType->getDirection()
            ];

            $multiplicator = $chargeType->getDirection() === 'credit' ? 1 : -1;

            if ($chargeType->isRentComponent()) {
                $breakdown['rentComponents'][] = $item;
                $breakdown['totals']['rent'] += $multiplicator * $amount;
            } elseif ($chargeType->isRecoverable()) {
                $breakdown['recoverableCharges'][] = $item;
                $breakdown['totals']['recoverableCharges'] += $multiplicator * $amount;
            } else {
                $breakdown['nonRecoverableCharges'][] = $item;
                $breakdown['totals']['nonRecoverableCharges'] += $multiplicator * $amount;
            }
        }

        $breakdown['totals']['total'] = 
            $breakdown['totals']['rent'] + 
            $breakdown['totals']['recoverableCharges'];

        return $breakdown;
    }

    public function getAnnualSummaryByHousing(int $year): array
{
    $start = new \DateTimeImmutable("$year-01-01 00:00:00");
    $end   = new \DateTimeImmutable("$year-12-31 23:59:59");

    return $this->createQueryBuilder('i')
        ->select('
            h.id AS housingId,
            h.title AS housingTitle,

            SUM(CASE WHEN ct.isRentComponent = true THEN i.amount ELSE 0 END) AS rentTotal,
            SUM(CASE WHEN ct.isRecoverable = true AND ct.isRentComponent = false THEN i.amount ELSE 0 END) AS recoverableChargesTotal,
            SUM(CASE WHEN ct.isRecoverable = false AND ct.isRentComponent = false THEN i.amount ELSE 0 END) AS nonRecoverableChargesTotal,
            SUM(i.amount) AS globalTotal
        ')
        ->join('i.housing', 'h')
        ->join('i.type', 'ct')
        ->andWhere('i.periodStart <= :end')
        ->andWhere('i.periodEnd IS NULL OR i.periodEnd >= :start')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->groupBy('h.id')
        ->orderBy('h.title', 'ASC')
        ->getQuery()
        ->getArrayResult();
}

    /**
     * Récupère les imputations d'un logement pour une année donnée
     * @return Imputation[]
     */
    public function findByHousingAndYear(Housing $housing, int $year): array
    {
        $start = new \DateTimeImmutable("$year-01-01");
        $end = new \DateTimeImmutable("$year-12-31");

        return $this->createQueryBuilder('i')
            ->join('i.type', 't')
            ->andWhere('i.housing = :housing')
            ->andWhere('i.periodStart <= :end')
            ->andWhere('i.periodEnd IS NULL OR i.periodEnd >= :start')
            ->setParameter('housing', $housing)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('i.periodStart', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le total des recettes (loyers) pour un logement et une année
     */
    public function getTotalRevenuesForYear(Housing $housing, int $year): float
    {
        $start = new \DateTimeImmutable("$year-01-01");
        $end = new \DateTimeImmutable("$year-12-31");

        $result = $this->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.amount), 0) as total')
            ->join('i.type', 't')
            ->andWhere('i.housing = :housing')
            ->andWhere('t.direction = :direction')
            ->andWhere('i.periodStart <= :end')
            ->andWhere('i.periodEnd IS NULL OR i.periodEnd >= :start')
            ->setParameter('housing', $housing)
            ->setParameter('direction', 'credit')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0.0);
    }

    /**
     * Calcule le total des charges déductibles pour un logement et une année
     */
    public function getTotalDeductibleChargesForYear(Housing $housing, int $year): float
    {
        $start = new \DateTimeImmutable("$year-01-01");
        $end = new \DateTimeImmutable("$year-12-31");

        $result = $this->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.amount), 0) as total')
            ->join('i.type', 't')
            ->andWhere('i.housing = :housing')
            ->andWhere('t.direction = :direction')
            ->andWhere('t.isTaxDeductible = :deductible')
            ->andWhere('i.periodStart <= :end')
            ->andWhere('i.periodEnd IS NULL OR i.periodEnd >= :start')
            ->setParameter('housing', $housing)
            ->setParameter('direction', 'debit')
            ->setParameter('deductible', true)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0.0);
    }
}