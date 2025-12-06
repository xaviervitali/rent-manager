<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ChargeTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ChargeTypeRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
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

    public function __construct()
    {
        $this->imputations = new ArrayCollection();
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
}