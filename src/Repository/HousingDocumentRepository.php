<?php

namespace App\Repository;

use App\Entity\HousingDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HousingDocument>
 */
class HousingDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HousingDocument::class);
    }

    public function getTotalSizeByHousing(int $housingId): int
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.fileSize) as totalSize')
            ->where('d.housing = :housingId')
            ->setParameter('housingId', $housingId)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }
}
