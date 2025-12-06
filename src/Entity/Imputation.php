<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ImputationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ImputationRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['imputation:read']],
    denormalizationContext: ['groups' => ['imputation:write']]
)]
class Imputation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['imputation:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Housing::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['imputation:read', 'imputation:write'])]
    private ?Housing $housing = null;

    #[ORM\ManyToOne(targetEntity: ChargeType::class, inversedBy: 'imputations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['imputation:read', 'imputation:write'])]
    private ?ChargeType $type = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['imputation:read', 'imputation:write'])]
    private ?string $amount = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['imputation:read', 'imputation:write'])]
    private ?\DateTimeImmutable $periodStart = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['imputation:read', 'imputation:write'])]
    private ?\DateTimeImmutable $periodEnd = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['imputation:read', 'imputation:write'])]
    private ?string $invoiceFile = null; // Chemin vers la facture PDF

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['imputation:read', 'imputation:write'])]
    private ?string $note = null;

    #[ORM\Column]
    #[Groups(['imputation:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['imputation:read'])]
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

    public function getHousing(): ?Housing
    {
        return $this->housing;
    }

    public function setHousing(?Housing $housing): static
    {
        $this->housing = $housing;
        return $this;
    }

    public function getType(): ?ChargeType
    {
        return $this->type;
    }

    public function setType(?ChargeType $type): static
    {
        $this->type = $type;
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

    public function getPeriodStart(): ?\DateTimeImmutable
    {
        return $this->periodStart;
    }

    public function setPeriodStart(\DateTimeImmutable $periodStart): static
    {
        $this->periodStart = $periodStart;
        return $this;
    }

    public function getPeriodEnd(): ?\DateTimeImmutable
    {
        return $this->periodEnd;
    }

    public function setPeriodEnd(\DateTimeImmutable $periodEnd): static
    {
        $this->periodEnd = $periodEnd;
        return $this;
    }

    public function getInvoiceFile(): ?string
    {
        return $this->invoiceFile;
    }

    public function setInvoiceFile(?string $invoiceFile): static
    {
        $this->invoiceFile = $invoiceFile;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;
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

    public function __toString(): string
    {
        return sprintf(
            '%s - %s €',
            $this->type?->getLabel() ?? 'Sans type',
            $this->amount ?? '0'
        );
    }
}