<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Housing;
use App\Entity\Lease;
use App\Entity\Imputation;
use App\Entity\Quittance;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final class CurrentUserExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private Security $security,
    ) {
    }

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
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        // Filtrer les Housing par user
        if (Housing::class === $resourceClass) {
            $queryBuilder->andWhere(sprintf('%s.user = :current_user', $rootAlias));
            $queryBuilder->setParameter('current_user', $user);
        }

        // Filtrer les Lease par le user du housing
        if (Lease::class === $resourceClass) {
            $queryBuilder->join(sprintf('%s.housing', $rootAlias), 'h');
            $queryBuilder->andWhere('h.user = :current_user');
            $queryBuilder->setParameter('current_user', $user);
        }

        // Filtrer les Imputation par le user du housing
        if (Imputation::class === $resourceClass) {
            $queryBuilder->join(sprintf('%s.housing', $rootAlias), 'h');
            $queryBuilder->andWhere('h.user = :current_user');
            $queryBuilder->setParameter('current_user', $user);
        }

        // Filtrer les Quittance par le user du housing via imputation
        if (Quittance::class === $resourceClass) {
            $queryBuilder->join(sprintf('%s.imputation', $rootAlias), 'i');
            $queryBuilder->join('i.housing', 'h');
            $queryBuilder->andWhere('h.user = :current_user');
            $queryBuilder->setParameter('current_user', $user);
        }
    }
}