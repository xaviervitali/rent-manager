<?php

namespace App\Repository;

use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tenant>
 */
class TenantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tenant::class);
    }

//    /**
//     * @return Tenant[] Returns an array of Tenant objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

    /**
     * Trouve les tenants appartenant à une liste d'utilisateurs
     *
     * @param int[] $userIds
     * @return Tenant[]
     */
    public function findByUsers(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        return $this->createQueryBuilder('t')
            ->where('t.user IN (:userIds)')
            ->setParameter('userIds', $userIds)
            ->orderBy('t.lastname', 'ASC')
            ->addOrderBy('t.firstname', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
