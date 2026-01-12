<?php

namespace App\Repository;

use App\Entity\Organization;
use App\Entity\OrganizationMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrganizationMember>
 */
class OrganizationMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganizationMember::class);
    }

    /**
     * Trouve le membership d'un utilisateur pour une organisation
     */
    public function findByUserAndOrganization(User $user, Organization $organization): ?OrganizationMember
    {
        return $this->createQueryBuilder('m')
            ->where('m.user = :user')
            ->andWhere('m.organization = :organization')
            ->setParameter('user', $user)
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Vérifie si un utilisateur est membre d'une organisation
     */
    public function isMember(User $user, Organization $organization): bool
    {
        return $this->findByUserAndOrganization($user, $organization) !== null;
    }

    /**
     * Vérifie si un utilisateur est admin d'une organisation
     */
    public function isAdmin(User $user, Organization $organization): bool
    {
        $member = $this->findByUserAndOrganization($user, $organization);
        return $member !== null && $member->isAdmin();
    }

    /**
     * Trouve tous les admins d'une organisation
     *
     * @return OrganizationMember[]
     */
    public function findAdminsByOrganization(Organization $organization): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.organization = :organization')
            ->andWhere('m.role = :role')
            ->setParameter('organization', $organization)
            ->setParameter('role', 'admin')
            ->getQuery()
            ->getResult();
    }
}
