<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Housing;
use App\Entity\Tenant;
use App\Entity\Lease;
use App\Entity\Imputation;
use App\Entity\Quittance;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends AbstractFixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    protected function loadData(ObjectManager $manager): void
    {
        // 1. USERS
        $this->createMany(User::class, 3, function (User $user, int $i) {
            $user->setEmail($this->faker->email());
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
            $user->setFirstname($this->faker->firstName());
            $user->setLastname($this->faker->lastName());
            $user->setPhone($this->faker->phoneNumber());
            $user->setRoles(['ROLE_USER']);
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setUpdatedAt(new \DateTimeImmutable());
        });

        // 2. HOUSINGS
        $this->createMany(Housing::class, 10, function (Housing $housing, int $i) {
            $housing->setTitle($this->faker->words(3, true));
            $housing->setCity($this->faker->city());
            $housing->setCityCode($this->faker->postcode());
            $housing->setAddress($this->faker->streetAddress());
            $housing->setBuilding($this->faker->optional()->word());
            $housing->setApartmentNumber($this->faker->optional()->numerify('##'));
            $housing->setNote($this->faker->optional()->sentence());
            $housing->setUser($this->getRandomReference(User::class));
            $housing->setCreatedAt(new \DateTimeImmutable());
            $housing->setUpdatedAt(new \DateTimeImmutable());
        });

        // 3. TENANTS
        $this->createMany(Tenant::class, 15, function (Tenant $tenant, int $i) {
            $tenant->setFirstname($this->faker->firstName());
            $tenant->setLastname($this->faker->lastName());
            $tenant->setEmail($this->faker->email());
            $tenant->setPhone($this->faker->phoneNumber());
            $tenant->setCreatedAt(new \DateTimeImmutable());
            $tenant->setUpdatedAt(new \DateTimeImmutable());
        });

        // 4. LEASES
        $this->createMany(Lease::class, 12, function (Lease $lease, int $i) {
            $lease->setHousing($this->getRandomReference(Housing::class));

            // Ajouter 1 à 2 locataires (pour gérer les colocations)
            $nbTenants = $this->faker->numberBetween(1, 2);
            for ($j = 0; $j < $nbTenants; $j++) {
                $tenant = $this->getRandomReference(Tenant::class);
                if (!$lease->getTenants()->contains($tenant)) {
                    $lease->addTenant($tenant);
                }
            }

            $startDate = $this->faker->dateTimeBetween('-2 years', '-1 month');
            $lease->setStartDate(\DateTimeImmutable::createFromMutable($startDate));

            // 80% de baux actifs, 20% terminés
            if ($this->faker->boolean(80)) {
                $lease->setStatus('active');
            } else {
                $lease->setStatus('terminated');
                $endDate = $this->faker->dateTimeBetween($startDate, 'now');
                $lease->setEndDate(\DateTimeImmutable::createFromMutable($endDate));
            }

            $lease->setCreatedAt(new \DateTimeImmutable());
            $lease->setUpdatedAt(new \DateTimeImmutable());
        });

        // 5. IMPUTATIONS
        $this->createMany(Imputation::class, 30, function (Imputation $imputation, int $i) {
            $lease = $this->getRandomReference(Lease::class);
            $imputation->setLease($lease);

            // Types d'imputations possibles
            $types = ['rent', 'charges', 'electricity', 'internet', 'insurance'];
            $type = $this->faker->randomElement($types);
            $imputation->setType($type);

            // Montants selon le type
            $amounts = [
                'rent' => $this->faker->randomFloat(2, 500, 2000),
                'charges' => $this->faker->randomFloat(2, 50, 200),
                'electricity' => $this->faker->randomFloat(2, 30, 100),
                'internet' => $this->faker->randomFloat(2, 20, 50),
                'insurance' => $this->faker->randomFloat(2, 10, 30),
            ];
            $imputation->setAmount((string) $amounts[$type]);

            $imputation->setStartDate($lease->getStartDate());

            // Si le bail est terminé, l'imputation aussi
            if ($lease->getStatus() === 'terminated' && $lease->getEndDate()) {
                $imputation->setEndDate($lease->getEndDate());
                $imputation->setStatus('inactive');
            } else {
                $imputation->setStatus('active');
            }

            // Périodicité
            $periodicities = ['monthly', 'quarterly', 'yearly', 'one_time'];
            $imputation->setPeriodicity($this->faker->randomElement($periodicities));

            $imputation->setNote($this->faker->optional()->sentence());
            $imputation->setCreatedAt(new \DateTimeImmutable());
            $imputation->setUpdatedAt(new \DateTimeImmutable());
        });

        // 6. QUITTANCES
        $this->createMany(Quittance::class, 50, function (Quittance $quittance, int $i) {
            $imputation = $this->getRandomReference(Imputation::class);
            $quittance->setImputation($imputation);

            // Numéro unique
            $quittance->setNumber('Q-' . date('Y') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT));

            $quittance->setAmount($imputation->getAmount());

            // Date de paiement entre la date de début du bail et maintenant
            $startDate = $imputation->getStartDate()->format('Y-m-d'); // Conversion en string
            $endDate = $imputation->getEndDate() ? $imputation->getEndDate()->format('Y-m-d') : 'now';

            $paymentDate = $this->faker->dateTimeBetween($startDate, $endDate);
            $quittance->setPaymentDate(\DateTimeImmutable::createFromMutable($paymentDate));

            // Période concernée (mois du paiement)
            $periodStart = new \DateTimeImmutable($paymentDate->format('Y-m-01'));
            $periodEnd = new \DateTimeImmutable($paymentDate->format('Y-m-t'));
            $quittance->setPeriodStart($periodStart);
            $quittance->setPeriodEnd($periodEnd);

            // Moyen de paiement
            $methods = ['bank_transfer', 'check', 'cash', 'card'];
            $quittance->setPaymentMethod($this->faker->randomElement($methods));

            $quittance->setReference($this->faker->bothify('??-########'));
            $quittance->setNote($this->faker->optional()->sentence());
            $quittance->setCreatedAt(new \DateTimeImmutable());
            $quittance->setUpdatedAt(new \DateTimeImmutable());
        });
    }

}