<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\LeaseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LeaseRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['lease:read']],
    denormalizationContext: ['groups' => ['lease:write']]
)]
class Lease
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['lease:read', 'housing:read'])] // ← Ajouté 'housing:read'
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Housing::class, inversedBy: 'leases')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['lease:read', 'lease:write'])]
    private ?Housing $housing = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['lease:read', 'lease:write', 'housing:read'])] // ← Ajouté 'housing:read'
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['lease:read', 'lease:write', 'housing:read'])] // ← Ajouté 'housing:read'
    private ?\DateTimeImmutable $endDate = null;


    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['lease:read', 'lease:write'])]
    private ?string $note = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['lease:read', 'lease:write'])]
    private ?string $contractFile = null; // Chemin vers le PDF du bail

    #[ORM\Column]
    #[Groups(['lease:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['lease:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, LeaseTenant>
     */
    #[ORM\OneToMany(targetEntity: LeaseTenant::class, mappedBy: 'lease', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['lease:read'])]
    private Collection $leaseTenants;

    #[ORM\ManyToOne(inversedBy: 'leases')]
    private ?User $user = null;

    public function __construct()
    {
        $this->leaseTenants = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ... getters/setters standards ...

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

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;
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

    public function getContractFile(): ?string
    {
        return $this->contractFile;
    }

    public function setContractFile(?string $contractFile): static
    {
        $this->contractFile = $contractFile;
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
     * @return Collection<int, LeaseTenant>
     */
    public function getLeaseTenants(): Collection
    {
        return $this->leaseTenants;
    }

    public function addLeaseTenant(LeaseTenant $leaseTenant): static
    {
        if (!$this->leaseTenants->contains($leaseTenant)) {
            $this->leaseTenants->add($leaseTenant);
            $leaseTenant->setLease($this);
        }
        return $this;
    }

    public function removeLeaseTenant(LeaseTenant $leaseTenant): static
    {
        if ($this->leaseTenants->removeElement($leaseTenant)) {
            if ($leaseTenant->getLease() === $this) {
                $leaseTenant->setLease(null);
            }
        }
        return $this;
    }

    /**
     * Retourne tous les locataires du bail
     * @return Collection<int, Tenant>
     */
    #[Groups(['lease:read', 'lease:write', 'housing:read'])] // ← Ajouté 'housing:read'

    public function getTenants(): Collection
    {
        return $this->leaseTenants->map(fn(LeaseTenant $lt) => $lt->getTenant());
    }

    public function __toString(): string
    {
        return sprintf(
            'Bail #%d - %s',
            $this->id ?? 0,
            $this->housing?->getTitle() ?? 'Sans logement'
        );
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