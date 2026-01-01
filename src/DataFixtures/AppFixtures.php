<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Housing;
use App\Entity\Lease;
use App\Entity\LeaseTenant;
use App\Entity\Tenant;
use App\Entity\ChargeType;
use App\Entity\Imputation;
use App\Entity\Payment;
use App\Entity\RentReceipt;
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

        // ========================================
        // 1. USERS (Propriétaires)
        // ========================================
        echo "👤 Création des utilisateurs (propriétaires)...\n";
        $this->createMany(User::class, 5, function (User $user, $i) {
            $user
                ->setEmail("proprietaire$i@gmail.com")
                ->setFirstname($this->faker->firstName())
                ->setLastname($this->faker->lastName())
                ->setPhone($this->faker->phoneNumber())
                ->setPassword($this->passwordHasher->hashPassword($user, 'password123'))
                ->setCreatedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween("-2 years", "-1 year")))
                ->setUpdatedAt(new \DateTimeImmutable());
            
            echo "  ✓ User: {$user->getEmail()}\n";
        });

        echo "✅ 5 utilisateurs créés\n\n";

        // ========================================
        // 1B. CHARGE TYPES (Catégories d'imputations)
        // ========================================
        echo "💰 Création des Catégories d'imputations...\n";
        
        $chargeTypesData = [
            ['label' => 'Loyer', 'recoverable' => true, 'rentComponent' => true, 'periodicity' => ChargeType::PERIODICITY_MONTHLY],
            ['label' => 'Eau', 'recoverable' => true, 'rentComponent' => true, 'periodicity' => ChargeType::PERIODICITY_MONTHLY],
            ['label' => 'Électricité', 'recoverable' => true, 'rentComponent' => true, 'periodicity' => ChargeType::PERIODICITY_MONTHLY],
            ['label' => 'Chauffage collectif', 'recoverable' => true, 'rentComponent' => true, 'periodicity' => ChargeType::PERIODICITY_MONTHLY],
            ['label' => 'Ordures ménagères', 'recoverable' => true, 'rentComponent' => true, 'periodicity' => ChargeType::PERIODICITY_QUARTERLY],
            ['label' => 'Entretien parties communes', 'recoverable' => true, 'rentComponent' => true, 'periodicity' => ChargeType::PERIODICITY_MONTHLY],
            ['label' => 'Taxe foncière', 'recoverable' => false, 'rentComponent' => false, 'periodicity' => ChargeType::PERIODICITY_ANNUAL],
            ['label' => 'Assurance propriétaire', 'recoverable' => false, 'rentComponent' => false, 'periodicity' => ChargeType::PERIODICITY_ANNUAL],
            ['label' => 'Travaux de rénovation', 'recoverable' => false, 'rentComponent' => false, 'periodicity' => ChargeType::PERIODICITY_ONE_TIME],
            ['label' => 'Frais de gestion', 'recoverable' => false, 'rentComponent' => false, 'periodicity' => ChargeType::PERIODICITY_MONTHLY],
        ];

        foreach ($chargeTypesData as $i => $data) {
            $chargeType = new ChargeType();
            $chargeType
                ->setLabel($data['label'])
                ->setIsRecoverable($data['recoverable'])
                ->setIsRentComponent($data['rentComponent'])
                ->setPeriodicity($data['periodicity'])
                ->setComment($this->faker->optional(0.3)->sentence())
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());
            
            $manager->persist($chargeType);
            $this->addReference(ChargeType::class . '_' . $i, $chargeType);
            
            $recov = $data['recoverable'] ? '✅ Récupérable' : '❌ Non récupérable';
            $period = $chargeType->getPeriodicityLabel();
            echo "  ✓ ChargeType: {$data['label']} ({$recov} - {$period})\n";
        }

        $manager->flush();
        echo "✅ 10 Catégories d'imputations créés\n\n";

        // ========================================
        // 2. TENANTS (Locataires)
        // ========================================
        echo "🏠 Création des locataires...\n";
        
        // On crée 30 locataires (6 par propriétaire en moyenne)
        $this->createMany(Tenant::class, 30, function (Tenant $tenant, $i) {
            $user = $this->getRandomReference(User::class);
            
            $tenant
                ->setUser($user)
                ->setFirstname($this->faker->firstName())
                ->setLastname($this->faker->lastName())
                ->setEmail($this->faker->email())
                ->setPhone($this->faker->phoneNumber())
                ->setNote($this->faker->optional(0.3)->sentence())
                ->setCreatedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween("-1 year", "-6 months")))
                ->setUpdatedAt(new \DateTimeImmutable());
            
            echo "  ✓ Tenant: {$tenant->getFirstname()} {$tenant->getLastname()} (Owner: {$user->getEmail()})\n";
        });

        echo "✅ 30 locataires créés\n\n";

// ========================================
// 3. HOUSINGS (Logements)
// ========================================
echo "🏢 Création des logements...\n";

$housingTypes = ['Studio', 'T1', 'T2', 'T3', 'T4', 'Maison'];
$allUsers = $this->getAllReferences(User::class);

// Max 3 logements par propriétaire
$maxHousingPerUser = 3;

foreach ($allUsers as $user) {
    $nbHousings = rand(1, $maxHousingPerUser); // entre 1 et 3 logements par propriétaire
    for ($i = 0; $i < $nbHousings; $i++) {
        $type = $this->faker->randomElement($housingTypes);
        $city = $this->faker->city();

        $housing = new Housing();
        $housing
            ->setUser($user)
            ->setTitle("{$type} {$city}")
            ->setAddress($this->faker->streetAddress())
            ->setCity($city)
            ->setCityCode($this->faker->postcode())
            ->setBuilding($this->faker->optional(0.3)->buildingNumber())
            ->setApartmentNumber($this->faker->optional(0.7)->bothify('##?'))
            ->setNote($this->faker->optional(0.4)->paragraph())
            ->setCreatedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween("-2 years", "-1 year")))
            ->setUpdatedAt(new \DateTimeImmutable());

        $manager->persist($housing);

        echo "  ✓ Housing: {$housing->getTitle()} (Owner: {$user->getEmail()})\n";
    }
}

$manager->flush();
echo "✅ Logements créés (max 3 par propriétaire)\n\n";


        // ========================================
        // 4. LEASES (Baux)
        // ========================================
        echo "📄 Création des baux...\n";
        
        // On crée un bail pour 80% des logements (environ 20 baux)
        $housings = $this->getAllReferences(Housing::class);
        $leaseCount = 0;
        
        foreach ($housings as $housing) {
            // 80% de chance d'avoir un bail
            if (!$this->faker->boolean(80)) {
                continue;
            }
            
            $user = $housing->getUser();
            $startDate = \DateTimeImmutable::createFromMutable(
                $this->faker->dateTimeBetween("-18 months", "-1 month")
            );
            
            $lease = new Lease();
            $lease
                ->setUser($user)
                ->setHousing($housing)
                ->setStartDate($startDate)
                ->setNote($this->faker->optional(0.3)->sentence())
                ->setCreatedAt($startDate)
                ->setUpdatedAt(new \DateTimeImmutable());
            
            // 20% des baux sont terminés
            if ($this->faker->boolean(20)) {
                $endDate = \DateTimeImmutable::createFromMutable(
                    $this->faker->dateTimeBetween($startDate->format('Y-m-d'), 'now')
                );
                $lease->setEndDate($endDate);
            }
            
            $manager->persist($lease);
            
            // Ajouter une référence manuelle pour les leases
            $this->addReference(Lease::class . '_' . $leaseCount, $lease);
            
            $status = $lease->getEndDate() ? '❌ Terminé' : '✅ Actif';
            echo "  ✓ Lease #{$leaseCount}: {$housing->getTitle()} ({$status})\n";
            $leaseCount++;
        }
        
        $manager->flush();
        echo "✅ {$leaseCount} baux créés\n\n";

        // ========================================
        // 5. LEASE_TENANTS (Relations Bail-Locataire)
        // ========================================
        echo "🔗 Création des relations bail-locataire...\n";
        
        $leaseTenantCount = 0;
        $allTenants = $this->getAllReferences(Tenant::class);

        for ($i = 0; $i < $leaseCount; $i++) {
            $lease = $this->getReference(Lease::class . '_' . $i, Lease::class);
            $user = $lease->getUser();
            
            // Filtrer les tenants qui appartiennent au même propriétaire
            $userTenants = array_filter($allTenants, function ($tenant) use ($user) {
                return $tenant->getUser() === $user;
            });

            if (empty($userTenants)) {
                echo "  ⚠️ Pas de tenant pour lease #{$i}\n";
                continue;
            }

            // Réindexer le tableau et mélanger
            $userTenants = array_values($userTenants);
            shuffle($userTenants);

            // Nombre de locataires pour ce bail (1 à 3)
            $nbTenantsForLease = rand(1, min(3, count($userTenants)));
            $percentages = $this->generatePercentages($nbTenantsForLease);

            for ($t = 0; $t < $nbTenantsForLease; $t++) {
                $tenant = $userTenants[$t];
                
                $leaseTenant = new LeaseTenant();
                $leaseTenant
                    ->setLease($lease)
                    ->setTenant($tenant)
                    ->setPercentage($percentages[$t])
                    ->setActive(!$lease->getEndDate()) // Inactif si bail terminé
                    ->setCreatedAt($lease->getStartDate())
                    ->setUpdatedAt($lease->getEndDate() ?? new \DateTimeImmutable());
                
                $manager->persist($leaseTenant);
                
                $activeStatus = $leaseTenant->isActive() ? '✅' : '❌';
                echo "  ✓ LeaseTenant #{$leaseTenantCount}: {$tenant->getFirstname()} {$tenant->getLastname()} ({$percentages[$t]}%) {$activeStatus}\n";
                $leaseTenantCount++;
            }
        }

        $manager->flush();
        
        echo "\n";
        echo "✅ {$leaseTenantCount} relations bail-locataire créées\n\n";

        // ========================================
        // 6. IMPUTATIONS (Charges sur les logements)
        // ========================================
        echo "📊 Création des imputations de charges...\n";
        
        $housings = $this->getAllReferences(Housing::class);
        $chargeTypes = [];
        for ($i = 0; $i < 10; $i++) {
            $chargeTypes[] = $this->getReference(ChargeType::class . '_' . $i, ChargeType::class);
        }
        
        $imputationCount = 0;
        
        foreach ($housings as $housing) {
            // Chaque logement a entre 2 et 5 charges différentes
            $nbCharges = rand(2, 5);
            shuffle($chargeTypes);
            
            for ($i = 0; $i < $nbCharges; $i++) {
                $chargeType = $chargeTypes[$i];
                
                // Période actuelle : débute il y a quelques mois et reste active (pas de fin)
                $periodStart = \DateTimeImmutable::createFromMutable(
                    $this->faker->dateTimeBetween("-6 months", "-1 month")
                );
                // Pas de date de fin = imputation active indéfiniment
                $periodEnd = null;
                
                $amount = match($chargeType->getLabel()) {
                    'Loyer' => rand(400, 1200),
                    'Eau' => rand(30, 80),
                    'Électricité' => rand(50, 150),
                    'Chauffage collectif' => rand(80, 200),
                    'Ordures ménagères' => rand(20, 40),
                    'Entretien parties communes' => rand(30, 60),
                    'Taxe foncière' => rand(800, 1500),
                    'Assurance propriétaire' => rand(200, 400),
                    'Travaux de rénovation' => rand(500, 3000),
                    'Frais de gestion' => rand(50, 150),
                    default => rand(50, 100)
                };
                
                $imputation = new Imputation();
                $imputation
                    ->setHousing($housing)
                    ->setType($chargeType)
                    ->setAmount((string)$amount)
                    ->setPeriodStart($periodStart)
                    ->setNote($this->faker->optional(0.3)->sentence())
                    ->setCreatedAt(new \DateTimeImmutable())
                    ->setUpdatedAt(new \DateTimeImmutable());
                
                $manager->persist($imputation);
                $this->addReference(Imputation::class . '_' . $imputationCount, $imputation);
                
                echo "  ✓ Imputation #{$imputationCount}: {$chargeType->getLabel()} - {$amount}€\n";
                $imputationCount++;
            }
        }
        
        $manager->flush();
        echo "✅ {$imputationCount} imputations créées\n\n";

        // ========================================
        // 7. PAYMENTS (Paiements de loyer)
        // ========================================
        echo "💳 Création des paiements...\n";
        
        $paymentMethods = ['bank_transfer', 'check', 'cash', 'direct_debit'];
        $paymentCount = 0;
        
        for ($i = 0; $i < $leaseCount; $i++) {
            $lease = $this->getReference(Lease::class . '_' . $i, Lease::class);
            
            // Skip les baux terminés
            if ($lease->getEndDate()) {
                continue;
            }
            
            // Créer entre 1 et 6 paiements par bail actif
            $nbPayments = rand(1, 6);
            
            for ($j = 0; $j < $nbPayments; $j++) {
                $paymentDate = \DateTimeImmutable::createFromMutable(
                    $this->faker->dateTimeBetween("-6 months", "now")
                );
                
                $amount = rand(400, 1500);
                
                $payment = new Payment();
                $payment
                    ->setLease($lease)
                    ->setDate($paymentDate)
                    ->setAmount((string)$amount)
                    ->setMethod($this->faker->randomElement($paymentMethods))
                    ->setReference($this->faker->optional(0.7)->bothify('REF-####-????'))
                    ->setNote($this->faker->optional(0.2)->sentence())
                    ->setCreatedAt(new \DateTimeImmutable())
                    ->setUpdatedAt(new \DateTimeImmutable());
                
                $manager->persist($payment);
                
                echo "  ✓ Payment #{$paymentCount}: {$amount}€ le {$paymentDate->format('Y-m-d')}\n";
                $paymentCount++;
            }
        }
        
        $manager->flush();
        echo "✅ {$paymentCount} paiements créés\n\n";

        // ========================================
        // 8. RENT RECEIPTS (Quittances de loyer)
        // ========================================
        echo "📄 Création des quittances de loyer...\n";
        
        $rentReceiptCount = 0;
        
        for ($i = 0; $i < $leaseCount; $i++) {
            $lease = $this->getReference(Lease::class . '_' . $i, Lease::class);
            
            // Skip les baux terminés
            if ($lease->getEndDate()) {
                continue;
            }
            
            // Créer entre 1 et 3 quittances par bail actif
            $nbReceipts = rand(1, 3);
            
            for ($j = 0; $j < $nbReceipts; $j++) {

                $start = $this->faker->dateTimeBetween("-6 months", "-1 month");
                $periodStart = \DateTimeImmutable::createFromMutable(
                    $start
                );
                $periodEnd = \DateTimeImmutable::createFromMutable($start->modify('+1 month'));
                
                $rentAmount = rand(400, 1200);
                $charges = rand(50, 200);
                $totalDue = $rentAmount + $charges;
                $totalPaid = $totalDue; // Supposons que tout est payé
                
                $rentReceipt = new RentReceipt();
                $rentReceipt
                    ->setLease($lease)
                    ->setPeriodStart($periodStart)
                    ->setPeriodEnd($periodEnd)
                    ->setRentAmount((string)$rentAmount)
                    ->setRecoverableCharges((string)$charges)
                    ->setTotalDue((string)$totalDue)
                    ->setTotalPaid((string)$totalPaid)
                    ->setPaymentMethod($this->faker->randomElement($paymentMethods))
                    ->setGeneratedAt(new \DateTimeImmutable())
                    ->setNote($this->faker->optional(0.2)->sentence())
                    ->setCreatedAt(new \DateTimeImmutable())
                    ->setUpdatedAt(new \DateTimeImmutable());
                
                $manager->persist($rentReceipt);
                
                echo "  ✓ RentReceipt #{$rentReceiptCount}: {$totalDue}€ ({$periodStart->format('m/Y')})\n";
                $rentReceiptCount++;
            }
        }
        
        $manager->flush();
        echo "✅ {$rentReceiptCount} quittances créées\n\n";
        
        echo "\n";
        echo "===========================================\n";
        echo "🎉 FIXTURES CHARGÉES AVEC SUCCÈS !\n";
        echo "===========================================\n";
        echo "📊 Résumé:\n";
        echo "  - 5 propriétaires\n";
        echo "  - 30 locataires\n";
        echo "  - 25 logements\n";
        echo "  - {$leaseCount} baux\n";
        echo "  - {$leaseTenantCount} relations bail-locataire\n";
        echo "  - 10 Catégories d'imputations\n";
        echo "  - {$imputationCount} imputations de charges\n";
        echo "  - {$paymentCount} paiements\n";
        echo "  - {$rentReceiptCount} quittances\n";
        echo "===========================================\n";
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
            $options = [[50, 50], [60, 40], [70, 30], [80, 20]];
            return $options[array_rand($options)];
        }
        
        if ($count === 3) {
            $options = [
                [34, 33, 33],
                [40, 30, 30],
                [50, 25, 25],
                [60, 20, 20],
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