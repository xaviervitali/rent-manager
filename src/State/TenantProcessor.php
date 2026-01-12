<?php
// src/State/TenantProcessor.php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Housing;
use App\Entity\Lease;
use App\Entity\LeaseTenant;
use App\Entity\Tenant;
use App\Service\OrganizationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class TenantProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private EntityManagerInterface $em,
        private Security $security,
        private OrganizationService $organizationService
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Tenant) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }


        // ⚡ Assigner automatiquement le user loggé
        $currentUser = $this->security->getUser();
        if ($currentUser) {
            $data->setUser($currentUser);
        } else {
            throw new \LogicException('Impossible de récupérer l\'utilisateur connecté.');
        }

        // ✅ Assigner l'organisation par défaut si non définie (création uniquement)
        $isUpdate = isset($context['previous_data']);
        if (!$data->getOrganization() && !$isUpdate) {
            $defaultOrg = $this->organizationService->getDefaultOrganization();
            if ($defaultOrg) {
                $data->setOrganization($defaultOrg);
            }
        }

        // Vérifier s'il y a un déménagement demandé
        if ($data->getNewHousingId()) {
            $this->handleMove($data);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    private function handleMove(Tenant $tenant): void
    {
        $newHousing = $this->em->getRepository(Housing::class)->find($tenant->getNewHousingId());
        if (!$newHousing) {
            throw new \InvalidArgumentException('Logement non trouvé');
        }

        $moveDate = $tenant->getMoveDate() 
            ? new \DateTimeImmutable($tenant->getMoveDate()) 
            : new \DateTimeImmutable();

        // 1. Terminer le bail actuel
        $currentLease = $tenant->getActiveLease();
        if ($currentLease) {
            foreach ($tenant->getLeaseTenants() as $leaseTenant) {
                if ($leaseTenant->getLease() === $currentLease && $leaseTenant->isActive()) {
                    $leaseTenant->setActive(false);
                }
            }

            $hasOtherActiveTenants = false;
            foreach ($currentLease->getLeaseTenants() as $lt) {
                if ($lt->isActive() && $lt->getTenant() !== $tenant) {
                    $hasOtherActiveTenants = true;
                    break;
                }
            }

            if (!$hasOtherActiveTenants) {
                $currentLease->setEndDate($moveDate->modify('-1 day'));
                $currentLease->setStatus('terminated');
            }
        }

        // 2. Vérifier si bail actif existe sur le nouveau logement
        $existingLease = $newHousing->getActiveLease();

        if ($existingLease) {
            $newLeaseTenant = new LeaseTenant();
            $newLeaseTenant->setLease($existingLease);
            $newLeaseTenant->setTenant($tenant);
            $newLeaseTenant->setPercentage(100);
            $newLeaseTenant->setActive(true);
            $this->em->persist($newLeaseTenant);
        } else {
            $newLease = new Lease();
            $newLease->setHousing($newHousing);
            $newLease->setStartDate($moveDate);
            $newLease->setStatus('active');
            $this->em->persist($newLease);

            $newLeaseTenant = new LeaseTenant();
            $newLeaseTenant->setLease($newLease);
            $newLeaseTenant->setTenant($tenant);
            $newLeaseTenant->setPercentage(100);
            $newLeaseTenant->setActive(true);
            $this->em->persist($newLeaseTenant);
        }

        $tenant->setNewHousingId(null);
        $tenant->setMoveDate(null);
    }
}