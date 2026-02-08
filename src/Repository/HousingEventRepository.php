<?php

namespace App\Repository;

use App\Entity\HousingEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HousingEvent>
 */
class HousingEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HousingEvent::class);
    }

    public function findByHousingOrderedByDate(int $housingId): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.housing = :housingId')
            ->setParameter('housingId', $housingId)
            ->orderBy('e.eventDate', 'DESC')
            ->addOrderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
