<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\ChargeTypeRepository;
use App\State\ChargeTypeProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ChargeTypeRepository::class)]
#[ORM\Table(name: 'rm_charge_type')]  // ← Ajoutez cette ligne
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(processor: ChargeTypeProcessor::class),
        new Put(processor: ChargeTypeProcessor::class),
        new Patch(processor: ChargeTypeProcessor::class),
        new Delete()
    ],
    normalizationContext: ['groups' => ['charge_type:read']],
    denormalizationContext: ['groups' => ['charge_type:write']]
)]

class ChargeType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['charge_type:read', 'imputation:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['charge_type:read', 'charge_type:write', 'imputation:read'])]
    private ?string $label = null;

    #[ORM\Column]
    #[Groups(['charge_type:read', 'charge_type:write', 'imputation:read'])]
    private bool $isRecoverable = false; // Récupérable auprès du locataire ?

    #[ORM\Column]
    #[Groups(['charge_type:read', 'charge_type:write'])]
    private bool $isRentComponent = false; // Apparaît sur la quittance ?

    #[ORM\Column(length: 50)]
    #[Groups(['charge_type:read', 'charge_type:write', 'imputation:read'])]
    private string $periodicity = 'monthly'; // monthly, quarterly, biannual, annual, one_time

    #[ORM\Column(length: 20)]
    #[Groups(['charge_type:read', 'charge_type:write', 'imputation:read',])]
    private string $direction = 'credit'; // credit (recette) ou debit (dépense)

    #[ORM\Column]
    #[Groups(['charge_type:read', 'charge_type:write', 'imputation:read'])]
    private bool $isTaxDeductible = false; // Déductible fiscalement ?

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['charge_type:read', 'charge_type:write'])]
    private ?string $comment = null;

    #[ORM\Column]
    #[Groups(['charge_type:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['charge_type:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Imputation>
     */
    #[ORM\OneToMany(targetEntity: Imputation::class, mappedBy: 'type')]
    private Collection $imputations;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['charge_type:read'])]
    private ?User $user = null;

    // Constantes pour les périodicités
    public const PERIODICITY_MONTHLY = 'monthly';        // Mensuel
    public const PERIODICITY_QUARTERLY = 'quarterly';    // Trimestriel
    public const PERIODICITY_BIANNUAL = 'biannual';      // Semestriel
    public const PERIODICITY_ANNUAL = 'annual';          // Annuel
    public const PERIODICITY_ONE_TIME = 'one_time';      // Ponctuel

    // Constantes pour la direction
    public const DIRECTION_DEBIT = 'debit';    // Charge (dépense)
    public const DIRECTION_CREDIT = 'credit';  // Recette (revenu)

    public function __construct()
    {
        $this->imputations = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->periodicity = self::PERIODICITY_MONTHLY;
        $this->direction = self::DIRECTION_CREDIT;
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

    public function isRecoverable(): bool
    {
        return $this->isRecoverable;
    }

    public function setIsRecoverable(bool $isRecoverable): static
    {
        $this->isRecoverable = $isRecoverable;
        return $this;
    }

    public function isRentComponent(): bool
    {
        return $this->isRentComponent;
    }

    public function setIsRentComponent(bool $isRentComponent): static
    {
        $this->isRentComponent = $isRentComponent;
        return $this;
    }

    public function getIsRecoverable(): bool
{
    return $this->isRecoverable;
}

public function getIsRentComponent(): bool
{
    return $this->isRentComponent;
}

    public function getPeriodicity(): string
    {
        return $this->periodicity;
    }

    public function setPeriodicity(string $periodicity): static
    {
        $allowedPeriods = [
            self::PERIODICITY_MONTHLY,
            self::PERIODICITY_QUARTERLY,
            self::PERIODICITY_BIANNUAL,
            self::PERIODICITY_ANNUAL,
            self::PERIODICITY_ONE_TIME,
        ];

        if (!in_array($periodicity, $allowedPeriods, true)) {
            throw new \InvalidArgumentException(
                sprintf('Periodicity must be one of: %s', implode(', ', $allowedPeriods))
            );
        }

        $this->periodicity = $periodicity;
        return $this;
    }

    /**
     * Retourne le label lisible de la périodicité
     */
    #[Groups(['charge_type:read', 'imputation:read'])]
    public function getPeriodicityLabel(): string
    {
        return match($this->periodicity) {
            self::PERIODICITY_MONTHLY => 'Mensuel',
            self::PERIODICITY_QUARTERLY => 'Trimestriel',
            self::PERIODICITY_BIANNUAL => 'Semestriel',
            self::PERIODICITY_ANNUAL => 'Annuel',
            self::PERIODICITY_ONE_TIME => 'Ponctuel',
            default => 'Inconnu',
        };
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function setDirection(string $direction): static
    {
        $allowedDirections = [self::DIRECTION_DEBIT, self::DIRECTION_CREDIT];

        if (!in_array($direction, $allowedDirections, true)) {
            throw new \InvalidArgumentException(
                sprintf('Direction must be one of: %s', implode(', ', $allowedDirections))
            );
        }

        $this->direction = $direction;
        return $this;
    }

    /**
     * Retourne le label lisible de la direction
     */
    #[Groups(['charge_type:read', 'imputation:read'])]
    public function getDirectionLabel(): string
    {
        return match($this->direction) {
            self::DIRECTION_DEBIT => 'Débit',
            self::DIRECTION_CREDIT => 'Crédit',
            default => 'Inconnu',
        };
    }

    public function isDebit(): bool
    {
        return $this->direction === self::DIRECTION_DEBIT;
    }

    public function isCredit(): bool
    {
        return $this->direction === self::DIRECTION_CREDIT;
    }

    public function isTaxDeductible(): bool
    {
        return $this->isTaxDeductible;
    }

    public function getIsTaxDeductible(): bool
    {
        return $this->isTaxDeductible;
    }

    public function setIsTaxDeductible(bool $isTaxDeductible): static
    {
        $this->isTaxDeductible = $isTaxDeductible;
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

    /**
     * @return Collection<int, Imputation>
     */
    public function getImputations(): Collection
    {
        return $this->imputations;
    }

    public function addImputation(Imputation $imputation): static
    {
        if (!$this->imputations->contains($imputation)) {
            $this->imputations->add($imputation);
            $imputation->setType($this);
        }
        return $this;
    }

    public function removeImputation(Imputation $imputation): static
    {
        if ($this->imputations->removeElement($imputation)) {
            if ($imputation->getType() === $this) {
                $imputation->setType(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->label ?? '';
    }


    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }
}