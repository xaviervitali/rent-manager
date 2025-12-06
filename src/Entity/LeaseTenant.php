<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\LeaseTenantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LeaseTenantRepository::class)]
#[ORM\Table(
    name: "lease_tenant",
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: "lease_tenant_unique", columns: ["lease_id", "tenant_id"])
    ]
)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['lease_tenant:read']],
    denormalizationContext: ['groups' => ['lease_tenant:write']]
)]
class LeaseTenant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['lease_tenant:read', 'lease:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Lease::class, inversedBy: 'leaseTenants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['lease_tenant:read', 'lease_tenant:write'])]
    private ?Lease $lease = null;

#[ORM\ManyToOne(targetEntity: Tenant::class, inversedBy: 'leaseTenants')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['lease_tenant:read', 'lease_tenant:write', 'lease:read'])]
    private ?Tenant $tenant = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['lease_tenant:read', 'lease_tenant:write', 'lease:read'])]
    private int $percentage = 100;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['lease_tenant:read', 'lease_tenant:write', 'lease:read'])]
    private bool $active = true;

    #[ORM\Column]
    #[Groups(['lease_tenant:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['lease_tenant:read'])]
    private ?\DateTimeImmutable $updatedAt = null;


    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->active = true;
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

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function setTenant(?Tenant $tenant): static
    {
        $this->tenant = $tenant;
        return $this;
    }

    public function getPercentage(): int
    {
        return $this->percentage;
    }

    public function setPercentage(int $percentage): static
    {
        if ($percentage < 0 || $percentage > 100) {
            throw new \InvalidArgumentException('Le pourcentage doit être entre 0 et 100');
        }
        $this->percentage = $percentage;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
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


}
