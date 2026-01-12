<?php

namespace App\Repository;

use App\Entity\Organization;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Organization>
 */
class OrganizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    /**
     * Trouve toutes les organisations dont un utilisateur est membre
     *
     * @return Organization[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->innerJoin('o.members', 'm')
            ->where('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les organisations où l'utilisateur est admin
     *
     * @return Organization[]
     */
    public function findWhereUserIsAdmin(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->innerJoin('o.members', 'm')
            ->where('m.user = :user')
            ->andWhere('m.role = :role')
            ->setParameter('user', $user)
            ->setParameter('role', 'admin')
            ->orderBy('o.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
