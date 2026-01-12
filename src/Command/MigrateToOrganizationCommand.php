<?php

namespace App\Command;

use App\Entity\ChargeType;
use App\Entity\Housing;
use App\Entity\Imputation;
use App\Entity\Lease;
use App\Entity\Tenant;
use App\Entity\User;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-to-organization',
    description: 'Migre les entités sans organisation vers l\'organisation par défaut de chaque utilisateur',
)]
class MigrateToOrganizationCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrganizationRepository $organizationRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Récupérer tous les utilisateurs
        $users = $this->entityManager->getRepository(User::class)->findAll();

        foreach ($users as $user) {
            $io->section("Utilisateur: " . $user->getEmail());

            // Trouver l'organisation de l'utilisateur
            $organizations = $this->organizationRepository->findByUser($user);

            if (empty($organizations)) {
                $io->warning("Aucune organisation pour cet utilisateur, ignoré.");
                continue;
            }

            $organization = $organizations[0]; // Première organisation
            $io->info("Organisation: " . $organization->getName());

            // Migrer les Housings
            $housings = $this->entityManager->getRepository(Housing::class)
                ->findBy(['user' => $user, 'organization' => null]);
            foreach ($housings as $housing) {
                $housing->setOrganization($organization);
            }
            $io->info(count($housings) . " housing(s) migrés");

            // Migrer les Tenants
            $tenants = $this->entityManager->getRepository(Tenant::class)
                ->findBy(['user' => $user, 'organization' => null]);
            foreach ($tenants as $tenant) {
                $tenant->setOrganization($organization);
            }
            $io->info(count($tenants) . " tenant(s) migrés");

            // Migrer les Leases
            $leases = $this->entityManager->getRepository(Lease::class)
                ->findBy(['user' => $user, 'organization' => null]);
            foreach ($leases as $lease) {
                $lease->setOrganization($organization);
            }
            $io->info(count($leases) . " lease(s) migrés");

            // Migrer les ChargeTypes sans user ou sans organisation
            $chargeTypes = $this->entityManager->getRepository(ChargeType::class)
                ->createQueryBuilder('c')
                ->where('c.user IS NULL OR c.organization IS NULL')
                ->getQuery()
                ->getResult();
            $migratedChargeTypes = 0;
            foreach ($chargeTypes as $chargeType) {
                if ($chargeType->getUser() === null) {
                    $chargeType->setUser($user);
                    $chargeType->setOrganization($organization);
                    $migratedChargeTypes++;
                } elseif ($chargeType->getUser() === $user && $chargeType->getOrganization() === null) {
                    $chargeType->setOrganization($organization);
                    $migratedChargeTypes++;
                }
            }
            if ($migratedChargeTypes > 0) {
                $io->info($migratedChargeTypes . " charge type(s) migrés");
            }

            // Migrer les Imputations via housing
            $imputations = $this->entityManager->getRepository(Imputation::class)
                ->createQueryBuilder('i')
                ->join('i.housing', 'h')
                ->where('h.user = :user')
                ->andWhere('i.organization IS NULL')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult();
            foreach ($imputations as $imputation) {
                $imputation->setOrganization($organization);
            }
            $io->info(count($imputations) . " imputation(s) migrées");
        }

        $this->entityManager->flush();

        $io->success('Migration terminée !');

        return Command::SUCCESS;
    }
}
