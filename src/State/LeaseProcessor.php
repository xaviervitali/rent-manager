<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Lease;
use App\Entity\LeaseTenant;
use App\Repository\TenantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class LeaseProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private TenantRepository $tenantRepository
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Lease
    {
        /** @var Lease $lease */
        $lease = $data;

        // Associer l'utilisateur connecté
        $user = $this->security->getUser();
        if ($user) {
            $lease->setUser($user);
        }

        // Récupérer les tenantIds depuis la requête (propriété virtuelle)
        $tenantIds = $context['request']?->toArray()['tenantIds'] ?? [];

        // Persister le bail d'abord
        $this->entityManager->persist($lease);

        // Créer les LeaseTenants
        if (!empty($tenantIds)) {
            $percentage = (int) floor(100 / count($tenantIds));

            foreach ($tenantIds as $tenantId) {
                $tenant = $this->tenantRepository->find($tenantId);

                if ($tenant) {
                    $leaseTenant = new LeaseTenant();
                    $leaseTenant->setLease($lease);
                    $leaseTenant->setTenant($tenant);
                    $leaseTenant->setPercentage($percentage);
                    $leaseTenant->setActive(true);

                    $this->entityManager->persist($leaseTenant);
                    $lease->addLeaseTenant($leaseTenant);
                }
            }
        }

        $this->entityManager->flush();

        return $lease;
    }
}
