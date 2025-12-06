<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\RentReceiptRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RentReceiptRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['rent_receipt:read']],
    denormalizationContext: ['groups' => ['rent_receipt:write']]
)]
class RentReceipt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['rent_receipt:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Lease::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['rent_receipt:read', 'rent_receipt:write'])]
    private ?Lease $lease = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['rent_receipt:read', 'rent_receipt:write'])]
    private ?\DateTimeImmutable $periodStart = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['rent_receipt:read', 'rent_receipt:write'])]
    private ?\DateTimeImmutable $periodEnd = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['rent_receipt:read', 'rent_receipt:write'])]
    private ?string $rentAmount = null; // Loyer hors charges

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['rent_receipt:read', 'rent_receipt:write'])]
    private ?string $recoverableCharges = null; // Charges récupérables

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['rent_receipt:read', 'rent_receipt:write'])]
    private ?string $totalDue = null; // Total facturé

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['rent_receipt:read', 'rent_receipt:write'])]
    private ?string $totalPaid = null; // Total payé

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['rent_receipt:read', 'rent_receipt:write'])]
    private ?string $paymentMethod = null;

    #[ORM\Column]
    #[Groups(['rent_receipt:read', 'rent_receipt:write'])]
    private ?\DateTimeImmutable $generatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['rent_receipt:read', 'rent_receipt:write'])]
    private ?string $pdfFile = null; // Chemin vers le PDF de la quittance

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['rent_receipt:read', 'rent_receipt:write'])]
    private ?string $note = null;

    #[ORM\Column]
    #[Groups(['rent_receipt:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['rent_receipt:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->generatedAt = new \DateTimeImmutable();
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

    public function getLease(): ?Lease
    {
        return $this->lease;
    }

    public function setLease(?Lease $lease): static
    {
        $this->lease = $lease;
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

    public function getRentAmount(): ?string
    {
        return $this->rentAmount;
    }

    public function setRentAmount(string $rentAmount): static
    {
        $this->rentAmount = $rentAmount;
        return $this;
    }

    public function getRecoverableCharges(): ?string
    {
        return $this->recoverableCharges;
    }

    public function setRecoverableCharges(string $recoverableCharges): static
    {
        $this->recoverableCharges = $recoverableCharges;
        return $this;
    }

    public function getTotalDue(): ?string
    {
        return $this->totalDue;
    }

    public function setTotalDue(string $totalDue): static
    {
        $this->totalDue = $totalDue;
        return $this;
    }

    public function getTotalPaid(): ?string
    {
        return $this->totalPaid;
    }

    public function setTotalPaid(string $totalPaid): static
    {
        $this->totalPaid = $totalPaid;
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getGeneratedAt(): ?\DateTimeImmutable
    {
        return $this->generatedAt;
    }

    public function setGeneratedAt(\DateTimeImmutable $generatedAt): static
    {
        $this->generatedAt = $generatedAt;
        return $this;
    }

    public function getPdfFile(): ?string
    {
        return $this->pdfFile;
    }

    public function setPdfFile(?string $pdfFile): static
    {
        $this->pdfFile = $pdfFile;
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
            'Quittance #%d - %s',
            $this->id ?? 0,
            $this->periodStart?->format('m/Y') ?? ''
        );
    }
}