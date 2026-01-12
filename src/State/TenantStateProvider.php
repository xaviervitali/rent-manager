<?php
// src/State/TenantStateProvider.php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\OrganizationRepository;
use App\Repository\TenantRepository;
use Symfony\Bundle\SecurityBundle\Security;

class TenantStateProvider implements ProviderInterface
{
    public function __construct(
        private TenantRepository $tenantRepository,
        private Security $security,
        private OrganizationRepository $organizationRepository
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return [];
        }

        // Récupérer tous les users autorisés (moi + membres de mes organisations)
        $allowedUserIds = $this->getAllowedUserIds($user);

        // Pour un item
        if (isset($uriVariables['id'])) {
            $tenant = $this->tenantRepository->find($uriVariables['id']);

            if ($tenant && in_array($tenant->getUser()?->getId(), $allowedUserIds, true)) {
                return $tenant;
            }

            return null;
        }

        // Pour la collection
        return $this->tenantRepository->findByUsers($allowedUserIds);
    }

    private function getAllowedUserIds(User $currentUser): array
    {
        $userIds = [$currentUser->getId()];

        $organizations = $this->organizationRepository->findByUser($currentUser);

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
}