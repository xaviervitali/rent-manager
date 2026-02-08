<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\CreditRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CreditRepository::class)]
#[ORM\Table(name: 'rm_credit')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Put(),
        new Patch(),
        new Delete()
    ],
    normalizationContext: ['groups' => ['credit:read']],
    denormalizationContext: ['groups' => ['credit:write']]
)]
class Credit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['credit:read', 'housing:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['credit:read', 'credit:write', 'housing:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    #[Groups(['credit:read', 'credit:write', 'housing:read'])]
    private ?string $amount = null;

    #[ORM\Column]
    #[Groups(['credit:read', 'credit:write', 'housing:read'])]
    private ?int $duration = null; // Durée en mois

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['credit:read', 'credit:write', 'housing:read'])]
    private ?\DateTime $startDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 3)]
    #[Groups(['credit:read', 'credit:write', 'housing:read'])]
    private ?string $rate = null; // Taux annuel en %

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['credit:read', 'credit:write', 'housing:read'])]
    private ?string $insuranceMonthly = null; // Assurance mensuelle

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['credit:read', 'credit:write'])]
    private ?string $comment = null;

    #[ORM\ManyToOne(inversedBy: 'credits')]
    #[Groups(['credit:read', 'credit:write'])]
    private ?Housing $housing = null;

    #[ORM\Column]
    #[Groups(['credit:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['credit:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getRate(): ?string
    {
        return $this->rate;
    }

    public function setRate(string $rate): static
    {
        $this->rate = $rate;
        return $this;
    }

    public function getInsuranceMonthly(): ?string
    {
        return $this->insuranceMonthly;
    }

    public function setInsuranceMonthly(?string $insuranceMonthly): static
    {
        $this->insuranceMonthly = $insuranceMonthly;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function getHousing(): ?Housing
    {
        return $this->housing;
    }

    public function setHousing(?Housing $housing): static
    {
        $this->housing = $housing;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // ========================================
    // Méthodes de calcul du crédit
    // ========================================

    /**
     * Calcule la mensualité hors assurance (formule d'annuité constante)
     */
    #[Groups(['credit:read', 'housing:read'])]
    public function getMonthlyPayment(): float
    {
        if (!$this->amount || !$this->rate || !$this->duration) {
            return 0;
        }

        $principal = (float) $this->amount;
        $monthlyRate = ((float) $this->rate / 100) / 12;
        $months = $this->duration;

        if ($monthlyRate === 0.0) {
            return round($principal / $months, 2);
        }

        // Formule : M = P * [r(1+r)^n] / [(1+r)^n - 1]
        $payment = $principal * ($monthlyRate * pow(1 + $monthlyRate, $months)) / (pow(1 + $monthlyRate, $months) - 1);

        return round($payment, 2);
    }

    /**
     * Mensualité totale (avec assurance)
     */
    #[Groups(['credit:read', 'housing:read'])]
    public function getTotalMonthlyPayment(): float
    {
        $payment = $this->getMonthlyPayment();
        $insurance = $this->insuranceMonthly ? (float) $this->insuranceMonthly : 0;

        return round($payment + $insurance, 2);
    }

    /**
     * Coût total du crédit (intérêts uniquement)
     */
    #[Groups(['credit:read', 'housing:read'])]
    public function getTotalInterest(): float
    {
        if (!$this->amount || !$this->duration) {
            return 0;
        }

        $totalPayments = $this->getMonthlyPayment() * $this->duration;
        return round($totalPayments - (float) $this->amount, 2);
    }

    /**
     * Coût total du crédit (intérêts + assurance)
     */
    #[Groups(['credit:read', 'housing:read'])]
    public function getTotalCost(): float
    {
        $interest = $this->getTotalInterest();
        $totalInsurance = $this->insuranceMonthly ? (float) $this->insuranceMonthly * $this->duration : 0;

        return round($interest + $totalInsurance, 2);
    }

    /**
     * Date de fin du crédit
     */
    #[Groups(['credit:read', 'housing:read'])]
    public function getEndDate(): ?\DateTime
    {
        if (!$this->startDate || !$this->duration) {
            return null;
        }

        $endDate = clone $this->startDate;
        $endDate->modify('+' . $this->duration . ' months');
        return $endDate;
    }

    /**
     * Génère le tableau d'amortissement complet
     * @return array<int, array{month: int, date: string, payment: float, principal: float, interest: float, insurance: float, totalPayment: float, remainingBalance: float}>
     */
    public function getAmortizationSchedule(): array
    {
        if (!$this->amount || !$this->rate || !$this->duration || !$this->startDate) {
            return [];
        }

        $schedule = [];
        $balance = (float) $this->amount;
        $monthlyRate = ((float) $this->rate / 100) / 12;
        $monthlyPayment = $this->getMonthlyPayment();
        $insurance = $this->insuranceMonthly ? (float) $this->insuranceMonthly : 0;
        $currentDate = clone $this->startDate;

        for ($month = 1; $month <= $this->duration; $month++) {
            $interest = round($balance * $monthlyRate, 2);
            $principal = round($monthlyPayment - $interest, 2);

            // Dernière échéance : ajustement pour solder le prêt
            if ($month === $this->duration) {
                $principal = $balance;
                $monthlyPayment = $principal + $interest;
            }

            $balance = max(0, round($balance - $principal, 2));

            $schedule[] = [
                'month' => $month,
                'date' => $currentDate->format('Y-m-d'),
                'payment' => $monthlyPayment,
                'principal' => $principal,
                'interest' => $interest,
                'insurance' => $insurance,
                'totalPayment' => round($monthlyPayment + $insurance, 2),
                'remainingBalance' => $balance,
            ];

            $currentDate->modify('+1 month');
        }

        return $schedule;
    }

    /**
     * Récupère les intérêts pour une année donnée (déductibles fiscalement)
     */
    public function getInterestForYear(int $year): float
    {
        $schedule = $this->getAmortizationSchedule();
        $total = 0;

        foreach ($schedule as $row) {
            $rowYear = (int) substr($row['date'], 0, 4);
            if ($rowYear === $year) {
                $total += $row['interest'];
            }
        }

        return round($total, 2);
    }

    /**
     * Récupère l'assurance pour une année donnée (déductible fiscalement)
     */
    public function getInsuranceForYear(int $year): float
    {
        $schedule = $this->getAmortizationSchedule();
        $total = 0;

        foreach ($schedule as $row) {
            $rowYear = (int) substr($row['date'], 0, 4);
            if ($rowYear === $year) {
                $total += $row['insurance'];
            }
        }

        return round($total, 2);
    }

    /**
     * Récupère le capital restant dû à la fin d'une année
     */
    public function getRemainingBalanceAtEndOfYear(int $year): float
    {
        $schedule = $this->getAmortizationSchedule();
        $lastBalance = (float) $this->amount;

        foreach ($schedule as $row) {
            $rowYear = (int) substr($row['date'], 0, 4);
            if ($rowYear <= $year) {
                $lastBalance = $row['remainingBalance'];
            }
        }

        return $lastBalance;
    }

    /**
     * Résumé annuel du crédit
     * @return array<int, array{year: int, interest: float, insurance: float, principal: float, remainingBalance: float}>
     */
    public function getYearlySummary(): array
    {
        $schedule = $this->getAmortizationSchedule();
        $yearly = [];

        foreach ($schedule as $row) {
            $year = (int) substr($row['date'], 0, 4);

            if (!isset($yearly[$year])) {
                $yearly[$year] = [
                    'year' => $year,
                    'interest' => 0,
                    'insurance' => 0,
                    'principal' => 0,
                    'remainingBalance' => 0,
                ];
            }

            $yearly[$year]['interest'] += $row['interest'];
            $yearly[$year]['insurance'] += $row['insurance'];
            $yearly[$year]['principal'] += $row['principal'];
            $yearly[$year]['remainingBalance'] = $row['remainingBalance'];
        }

        // Arrondir les totaux
        foreach ($yearly as &$y) {
            $y['interest'] = round($y['interest'], 2);
            $y['insurance'] = round($y['insurance'], 2);
            $y['principal'] = round($y['principal'], 2);
        }

        return array_values($yearly);
    }
}
