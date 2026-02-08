<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\AssetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AssetRepository::class)]
#[ORM\Table(name: 'rm_asset')]
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
    normalizationContext: ['groups' => ['asset:read']],
    denormalizationContext: ['groups' => ['asset:write']]
)]
class Asset
{
    // Types d'immobilisation selon le BOFIP
    public const TYPE_STRUCTURE = 'structure';           // Gros oeuvre > 50 ans
    public const TYPE_FACADE = 'facade';                 // Façade 20-50 ans
    public const TYPE_AGENCEMENT = 'agencement';         // Agencement 15-30 ans
    public const TYPE_EQUIPEMENT = 'equipement';         // Équipement IGT 5-15 ans
    public const TYPE_MOBILIER = 'mobilier';             // Mobilier 5-10 ans
    public const TYPE_TRAVAUX = 'travaux';               // Travaux 10-15 ans
    public const TYPE_FRAIS_ACQUISITION = 'frais_acquisition'; // Frais notaire, dossier prêt, etc.

    // Durées d'amortissement recommandées par défaut (en années)
    public const DEFAULT_DURATIONS = [
        self::TYPE_STRUCTURE => 50,
        self::TYPE_FACADE => 30,
        self::TYPE_AGENCEMENT => 15,
        self::TYPE_EQUIPEMENT => 10,
        self::TYPE_MOBILIER => 7,
        self::TYPE_TRAVAUX => 10,
        self::TYPE_FRAIS_ACQUISITION => 20,
    ];

    // Ventilation BOFIP recommandée (en %)
    public const BOFIP_VENTILATION = [
        self::TYPE_STRUCTURE => 45,      // 40-50%
        self::TYPE_FACADE => 10,         // 5-20%
        self::TYPE_AGENCEMENT => 8,      // 5-10%
        self::TYPE_EQUIPEMENT => 22,     // 20-25%
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['asset:read', 'housing:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['asset:read', 'asset:write', 'housing:read'])]
    private ?string $label = null;

    #[ORM\Column(length: 50)]
    #[Groups(['asset:read', 'asset:write', 'housing:read'])]
    private string $type = self::TYPE_MOBILIER;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    #[Groups(['asset:read', 'asset:write', 'housing:read'])]
    private ?string $acquisitionValue = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['asset:read', 'asset:write', 'housing:read'])]
    private ?\DateTimeInterface $acquisitionDate = null;

    #[ORM\Column]
    #[Groups(['asset:read', 'asset:write', 'housing:read'])]
    private int $depreciationDuration = 10; // Durée en années

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['asset:read', 'asset:write'])]
    private ?string $comment = null;

    #[ORM\ManyToOne(inversedBy: 'assets')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['asset:read', 'asset:write'])]
    private ?Housing $housing = null;

    #[ORM\Column]
    #[Groups(['asset:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['asset:read'])]
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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $allowedTypes = [
            self::TYPE_STRUCTURE,
            self::TYPE_FACADE,
            self::TYPE_AGENCEMENT,
            self::TYPE_EQUIPEMENT,
            self::TYPE_MOBILIER,
            self::TYPE_TRAVAUX,
            self::TYPE_FRAIS_ACQUISITION,
        ];

        if (!in_array($type, $allowedTypes, true)) {
            throw new \InvalidArgumentException(
                sprintf('Type must be one of: %s', implode(', ', $allowedTypes))
            );
        }

        $this->type = $type;
        return $this;
    }

    #[Groups(['asset:read', 'housing:read'])]
    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_STRUCTURE => 'Structure / Gros œuvre',
            self::TYPE_FACADE => 'Façade',
            self::TYPE_AGENCEMENT => 'Agencement',
            self::TYPE_EQUIPEMENT => 'Équipement (IGT)',
            self::TYPE_MOBILIER => 'Mobilier',
            self::TYPE_TRAVAUX => 'Travaux',
            self::TYPE_FRAIS_ACQUISITION => 'Frais d\'acquisition',
            default => 'Inconnu',
        };
    }

    public function getAcquisitionValue(): ?string
    {
        return $this->acquisitionValue;
    }

    public function setAcquisitionValue(string $acquisitionValue): static
    {
        $this->acquisitionValue = $acquisitionValue;
        return $this;
    }

    public function getAcquisitionDate(): ?\DateTimeInterface
    {
        return $this->acquisitionDate;
    }

    public function setAcquisitionDate(\DateTimeInterface $acquisitionDate): static
    {
        $this->acquisitionDate = $acquisitionDate;
        return $this;
    }

    public function getDepreciationDuration(): int
    {
        return $this->depreciationDuration;
    }

    public function setDepreciationDuration(int $depreciationDuration): static
    {
        $this->depreciationDuration = $depreciationDuration;
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
    // Méthodes de calcul d'amortissement
    // ========================================

    /**
     * Taux d'amortissement annuel (en %)
     */
    #[Groups(['asset:read', 'housing:read'])]
    public function getDepreciationRate(): float
    {
        if ($this->depreciationDuration <= 0) {
            return 0;
        }
        return round(100 / $this->depreciationDuration, 2);
    }

    /**
     * Montant d'amortissement annuel
     */
    #[Groups(['asset:read', 'housing:read'])]
    public function getAnnualDepreciation(): float
    {
        if ($this->depreciationDuration <= 0 || !$this->acquisitionValue) {
            return 0;
        }
        return round((float) $this->acquisitionValue / $this->depreciationDuration, 2);
    }

    /**
     * Date de fin d'amortissement
     */
    #[Groups(['asset:read', 'housing:read'])]
    public function getDepreciationEndDate(): ?\DateTimeInterface
    {
        if (!$this->acquisitionDate) {
            return null;
        }

        $endDate = \DateTime::createFromInterface($this->acquisitionDate);
        $endDate->modify('+' . $this->depreciationDuration . ' years');
        return $endDate;
    }

    /**
     * Calcule l'amortissement pour une année donnée (avec prorata temporis)
     */
    public function getDepreciationForYear(int $year): float
    {
        if (!$this->acquisitionDate || !$this->acquisitionValue) {
            return 0;
        }

        $acquisitionYear = (int) $this->acquisitionDate->format('Y');
        $endYear = $acquisitionYear + $this->depreciationDuration;

        // Avant l'acquisition ou après la fin d'amortissement
        if ($year < $acquisitionYear || $year > $endYear) {
            return 0;
        }

        $annualAmount = $this->getAnnualDepreciation();

        // Première année : prorata temporis
        if ($year === $acquisitionYear) {
            $acquisitionDay = (int) $this->acquisitionDate->format('z') + 1; // Jour de l'année (1-366)
            $daysInYear = (int) $this->acquisitionDate->format('L') ? 366 : 365;
            $remainingDays = $daysInYear - $acquisitionDay + 1;
            return round($annualAmount * $remainingDays / $daysInYear, 2);
        }

        // Dernière année : prorata temporis (complément de la première année)
        if ($year === $endYear) {
            $acquisitionDay = (int) $this->acquisitionDate->format('z') + 1;
            $daysInYear = (int) $this->acquisitionDate->format('L') ? 366 : 365;
            $usedDays = $acquisitionDay - 1;
            return round($annualAmount * $usedDays / $daysInYear, 2);
        }

        // Années intermédiaires : amortissement complet
        return $annualAmount;
    }

    /**
     * Calcule l'amortissement cumulé jusqu'à une date donnée
     */
    public function getCumulativeDepreciation(\DateTimeInterface $date): float
    {
        if (!$this->acquisitionDate || !$this->acquisitionValue) {
            return 0;
        }

        $total = 0;
        $currentYear = (int) $this->acquisitionDate->format('Y');
        $targetYear = (int) $date->format('Y');

        for ($year = $currentYear; $year <= $targetYear; $year++) {
            $total += $this->getDepreciationForYear($year);
        }

        // Ne pas dépasser la valeur d'acquisition
        return min($total, (float) $this->acquisitionValue);
    }

    /**
     * Valeur nette comptable à une date donnée
     */
    public function getNetBookValue(\DateTimeInterface $date): float
    {
        if (!$this->acquisitionValue) {
            return 0;
        }
        return (float) $this->acquisitionValue - $this->getCumulativeDepreciation($date);
    }

    /**
     * Vérifie si l'immobilisation est entièrement amortie
     */
    public function isFullyDepreciated(\DateTimeInterface $date = null): bool
    {
        $date = $date ?? new \DateTime();
        return $this->getNetBookValue($date) <= 0;
    }
}
