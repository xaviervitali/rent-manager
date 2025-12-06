<?php
// src/State/TenantStateProvider.php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Tenant;
use App\Repository\TenantRepository;
use Symfony\Bundle\SecurityBundle\Security;

class TenantStateProvider implements ProviderInterface
{
    public function __construct(
        private TenantRepository $tenantRepository,
        private Security $security
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            return [];
        }

        // Pour un item
        if (isset($uriVariables['id'])) {
            $tenant = $this->tenantRepository->find($uriVariables['id']);
            
            if ($tenant && $tenant->getUser() === $user) {
                return $tenant;
            }
            
            return null;
        }

        // Pour la collection
        return $this->tenantRepository->findBy(['user' => $user]);
    }
}