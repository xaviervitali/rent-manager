<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Housing;
use App\Entity\Lease;
use App\Entity\LeaseTenant;
use App\Entity\Tenant;
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
        echo "🚀 Chargement des fixtures...\n\n";

        // 1. USERS
        echo "📝 Création des users...\n";
        $this->createMany(User::class, 10, function (User $user, $i) {
            $user
                ->setEmail("user$i@gmail.com")
                ->setFirstname($this->faker->firstName())      // ⚠️ firstname (minuscule)
                ->setLastname($this->faker->lastName())        // ⚠️ lastname (minuscule)
                ->setCreatedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween("-1 year")))
                ->setPassword($this->passwordHasher->hashPassword($user, 'password123'))
                ->setUpdatedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween("-1 year")))
                ->setPhone($this->faker->phoneNumber());
        });

        // 2. TENANTS
        echo "📝 Création des tenants...\n";
        $this->createMany(Tenant::class, 25, function ($tenant) {
            $tenant
                ->setUser($this->getRandomReference(User::class))
                ->setCreatedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween("-1 year")))
                ->setUpdatedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween("-1 year")))
                ->setEmail($this->faker->email())
                ->setFirstname($this->faker->firstName())      // ⚠️ firstname (minuscule)
                ->setLastname($this->faker->lastName())        // ⚠️ lastname (minuscule)
                ->setPhone($this->faker->phoneNumber());
                // ⚠️ Supprimé le doublon ->setUser()
        });

        // 3. HOUSINGS
        echo "📝 Création des logements...\n";
        $this->createMany(Housing::class, 20, function ($housing) {
            $housing
                ->setCreatedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween("-1 year")))
                ->setUpdatedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween("-1 year")))
                ->setTitle($this->faker->catchPhrase())
                ->setCity($this->faker->city())
                ->setCityCode($this->faker->postcode())
                ->setAddress($this->faker->streetAddress())
                ->setUser($this->getRandomReference(User::class))
                ->setNote($this->faker->paragraph());
        });

        // ⚠️ Flush pour avoir les housings en base
        $manager->flush();

        // 4. LEASES
        echo "📝 Création des baux...\n";
        $housings = $this->getAllReferences(Housing::class);
        $tenants = $this->getAllReferences(Tenant::class);

        foreach ($housings as $housing) {
            if (!$this->faker->boolean(70)) { // 70% de chance d'avoir un lease
                continue;
            }

            $user = $housing->getUser();
            $startDate = \DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween("-2 years", "-6 months"));
            
            $lease = new Lease();
            $lease
                ->setUser($user)
                ->setHousing($housing)
                ->setCreatedAt($startDate)
                ->setUpdatedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween("-1 year")))
                ->setStartDate($startDate)
                ->setNote($this->faker->paragraph());

            // 30% de chance d'avoir une date de fin
            if ($this->faker->boolean(30)) {
                $endDate = \DateTimeImmutable::createFromMutable(
                    $this->faker->dateTimeBetween($startDate->format('Y-m-d'), 'now')
                );
                $lease->setEndDate($endDate);
            }

            $manager->persist($lease); // ⚠️ Il faut persister le lease !
        }

        $manager->flush();
        $manager->clear(); // Clear pour forcer le rechargement

        // 5. LEASE_TENANTS
        echo "\n📝 Création des relations lease-tenant...\n";
        
        $leases = $manager->getRepository(Lease::class)->findAll(); // ⚠️ Recharger depuis la DB
        $tenants = $manager->getRepository(Tenant::class)->findAll(); // ⚠️ Recharger depuis la DB
        
        $existingCombinations = []; // ⚠️ Initialiser la variable
        $created = 0;
        $skipped = 0;

        foreach ($leases as $lease) {
            $user = $lease->getUser();
            
            $userTenants = array_filter($tenants, function ($tenant) use ($user) {
                return $tenant->getUser() === $user;
            });

            if (empty($userTenants)) {
                $skipped++;
                continue;
            }
            
            $userTenants = array_values($userTenants);
            shuffle($userTenants); // ⚠️ Mélanger pour plus de variété

            // Nombre de tenants à assigner (1 à 3, mais pas plus que disponible)
            $nbTenantsForLease = rand(1, min(3, count($userTenants)));
            
            // Générer les pourcentages
            $percentages = $this->generatePercentages($nbTenantsForLease);

            for ($i = 0; $i < $nbTenantsForLease; $i++) {
                $tenant = $userTenants[$i];

                // Vérifier l'unicité
                $key = sprintf('%d-%d', $lease->getId(), $tenant->getId());

                if (isset($existingCombinations[$key])) {
                    continue;
                }

                $existingCombinations[$key] = true;

                $leaseTenant = new LeaseTenant();
                $leaseTenant->setLease($lease);
                $leaseTenant->setTenant($tenant);
                $leaseTenant->setPercentage($percentages[$i]); // ⚠️ Ajout du percentage
                $leaseTenant->setCreatedAt($lease->getStartDate());

                if ($lease->getEndDate()) {
                    $leaseTenant->setUpdatedAt($lease->getEndDate());
                } else {
                    $leaseTenant->setUpdatedAt(new \DateTimeImmutable());
                }

                $manager->persist($leaseTenant);
                $created++;

                // ⚠️ Correction de la syntaxe (enlever le "}" en trop)
                echo "  ✓ LeaseTenant #{$created} : Lease #{$lease->getId()} (User: {$user->getEmail()}) + Tenant #{$tenant->getId()} ({$tenant->getFirstname()} {$tenant->getLastname()}) - {$percentages[$i]}%\n";
            }
        }

        $manager->flush();

        echo "\n✅ {$created} LeaseTenant créés avec succès\n";
        if ($skipped > 0) {
            echo "⚠️  {$skipped} leases ignorés (pas de tenant pour le user)\n";
        }
        echo "🎉 Fixtures chargées !\n";
    }

    /**
     * Génère des pourcentages qui totalisent 100%
     */
    private function generatePercentages(int $count): array
    {
        if ($count === 1) {
            return [100];
        }
        
        if ($count === 2) {
            $options = [[50, 50], [60, 40], [70, 30]];
            return $options[array_rand($options)];
        }
        
        if ($count === 3) {
            $options = [
                [34, 33, 33],
                [40, 30, 30],
                [50, 25, 25],
            ];
            return $options[array_rand($options)];
        }
        
        // Par défaut, répartition égale
        $equal = floor(100 / $count);
        $percentages = array_fill(0, $count, $equal);
        $percentages[0] += 100 - array_sum($percentages);
        
        return $percentages;
    }
}