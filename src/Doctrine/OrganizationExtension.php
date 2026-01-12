<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\ChargeType;
use App\Entity\Housing;
use App\Entity\Imputation;
use App\Entity\Lease;
use App\Entity\RentReceipt;
use App\Entity\Tenant;
use App\Entity\User;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Extension Doctrine qui filtre automatiquement les entités.
 *
 * Option 1 : Les données appartiennent à un USER, et les membres
 * des mêmes organisations peuvent les voir.
 *
 * Logique :
 * - user = currentUser (mes propres données)
 * - OU user IN (users qui partagent au moins une organisation avec moi)
 */
class OrganizationExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    // Entités qui doivent être filtrées
    private const FILTERED_ENTITIES = [
        Housing::class,
        Tenant::class,
        Lease::class,
        ChargeType::class,
        Imputation::class,
        RentReceipt::class,
    ];

    public function __construct(
        private Security $security,
        private OrganizationRepository $organizationRepository
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        // Ne pas filtrer si ce n'est pas une entité concernée
        if (!in_array($resourceClass, self::FILTERED_ENTITIES, true)) {
            return;
        }

        $user = $this->security->getUser();

        // Pas de filtrage pour les utilisateurs non connectés
        if (!$user instanceof User) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        // Récupérer tous les users qui partagent au moins une organisation avec moi
        $allowedUserIds = $this->getAllowedUserIds($user);

        // Appliquer le filtre selon l'entité
        $this->applyUserFilter($queryBuilder, $rootAlias, $resourceClass, $allowedUserIds);
    }

    /**
     * Récupère les IDs de tous les users autorisés :
     * - L'utilisateur lui-même
     * - Les membres des organisations dont il fait partie
     */
    private function getAllowedUserIds(User $currentUser): array
    {
        $userIds = [$currentUser->getId()];

        // Récupérer les organisations de l'utilisateur
        $organizations = $this->organizationRepository->findByUser($currentUser);

        // Pour chaque organisation, récupérer les membres
        foreach ($organizations as $organization) {
            foreach ($organization->getMembers() as $member) {
                $memberId = $member->getUser()?->getId();
                if ($memberId !== null && !in_array($memberId, $userIds, true)) {
                    $userIds[] = $memberId;
                }
            }
        }

        return $userIds;
    }

    /**
     * Applique le filtre par user selon le type d'entité
     */
    private function applyUserFilter(
        QueryBuilder $queryBuilder,
        string $rootAlias,
        string $resourceClass,
        array $allowedUserIds
    ): void {
        $paramName = 'allowed_user_ids_' . uniqid();

        // Entités avec relation directe vers User
        if (in_array($resourceClass, [Housing::class, Tenant::class, Lease::class, ChargeType::class], true)) {
            $queryBuilder
                ->andWhere("$rootAlias.user IN (:$paramName)")
                ->setParameter($paramName, $allowedUserIds);
            return;
        }

        // Imputation : passe par Housing
        if ($resourceClass === Imputation::class) {
            $housingAlias = 'h_filter_' . uniqid();
            $queryBuilder
                ->join("$rootAlias.housing", $housingAlias)
                ->andWhere("$housingAlias.user IN (:$paramName)")
                ->setParameter($paramName, $allowedUserIds);
            return;
        }

        // RentReceipt : passe par Lease
        if ($resourceClass === RentReceipt::class) {
            $leaseAlias = 'l_filter_' . uniqid();
            $queryBuilder
                ->join("$rootAlias.lease", $leaseAlias)
                ->andWhere("$leaseAlias.user IN (:$paramName)")
                ->setParameter($paramName, $allowedUserIds);
            return;
        }

        // Par défaut : ne rien montrer
        $queryBuilder->andWhere('1 = 0');
    }
}
