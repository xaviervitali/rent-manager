<?php

namespace App\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Lease;
use App\Entity\LeaseTenant;
use App\Repository\TenantRepository;
use App\Repository\LeaseTenantRepository;
use App\Repository\LeaseRepository;
use App\Service\OrganizationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class LeaseProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private TenantRepository $tenantRepository,
        private LeaseTenantRepository $leaseTenantRepository,
        private LeaseRepository $leaseRepository,
        private OrganizationService $organizationService
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Lease
    {
        /** @var Lease $lease */
        $lease = $data;

        // Déterminer si c'est une création ou une mise à jour
        $isUpdate = $operation instanceof Put || $operation instanceof Patch;

        // Pour les mises à jour, charger l'entité existante depuis la base de données
        if ($isUpdate && isset($uriVariables['id'])) {
            $existingLease = $this->leaseRepository->find($uriVariables['id']);
            if ($existingLease) {
                // Copier les données du payload vers l'entité existante
                if ($lease->getHousing()) {
                    $existingLease->setHousing($lease->getHousing());
                }
                if ($lease->getStartDate()) {
                    $existingLease->setStartDate($lease->getStartDate());
                }
                if ($lease->getEndDate()) {
                    $existingLease->setEndDate($lease->getEndDate());
                }
                $existingLease->setNote($lease->getNote());

                // Utiliser l'entité existante
                $lease = $existingLease;
            }
        }

        // Associer l'utilisateur connecté
        $user = $this->security->getUser();
        if ($user && !$lease->getUser()) {
            $lease->setUser($user);
        }

        // ✅ Assigner l'organisation par défaut si non définie (création uniquement)
        if (!$lease->getOrganization() && !$isUpdate) {
            $defaultOrg = $this->organizationService->getDefaultOrganization();
            if ($defaultOrg) {
                $lease->setOrganization($defaultOrg);
            }
        }

        // Récupérer les tenantIds depuis la requête (propriété virtuelle)
        $requestData = $context['request']?->toArray() ?? [];
        $tenantIds = $requestData['tenantIds'] ?? null;

        // Gérer les LeaseTenants seulement si tenantIds est fourni
        if ($tenantIds !== null && is_array($tenantIds)) {
            // Si c'est une mise à jour, supprimer les anciens LeaseTenants
            if ($isUpdate && $lease->getId()) {
                $existingLeaseTenants = $this->leaseTenantRepository->findBy(['lease' => $lease]);
                foreach ($existingLeaseTenants as $existingLeaseTenant) {
                    $this->entityManager->remove($existingLeaseTenant);
                }
                // Vider la collection du côté de l'entité
                foreach ($lease->getLeaseTenants()->toArray() as $lt) {
                    $lease->removeLeaseTenant($lt);
                }
                // Flush les suppressions avant de créer les nouveaux
                $this->entityManager->flush();
            }

            // Créer les nouveaux LeaseTenants
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
        }

        // Utiliser le processor par défaut d'API Platform
        return $this->persistProcessor->process($lease, $operation, $uriVariables, $context);
    }
}
