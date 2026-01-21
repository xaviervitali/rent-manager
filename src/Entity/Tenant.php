<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\TenantRepository;
use App\State\TenantProcessor;
use App\State\TenantStateProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TenantRepository::class)]
#[ORM\Table(name: 'rm_tenant')]  // ← Ajoutez cette ligne
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['tenant:read']],
    denormalizationContext: ['groups' => ['tenant:write']],
    processor: TenantProcessor::class,
        provider: TenantStateProvider::class  // ← Ajouter
)]

class Tenant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['tenant:read', 'lease:read', 'housing:read', 'lease_tenant:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['tenant:read', 'tenant:write', 'lease:read', 'housing:read'])]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Groups(['tenant:read', 'tenant:write', 'lease:read', 'housing:read'])]
    private ?string $lastname = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)] // ✅ Ajouter unique: true
    #[Groups(['tenant:read', 'tenant:write'])]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['tenant:read', 'tenant:write'])]
    private ?string $phone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['tenant:read', 'tenant:write'])]
    private ?string $note = null;

    #[ORM\Column]
    #[Groups(['tenant:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['tenant:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    // ✅ Relation avec LeaseTenant (pas directement avec Lease)
    #[ORM\OneToMany(targetEntity: LeaseTenant::class, mappedBy: 'tenant', cascade: ['persist', 'remove'])]
    #[Groups(['tenant:read'])]
    private Collection $leaseTenants;


    #[Groups(['tenant:write'])]
    private ?int $newHousingId = null;

    #[Groups(['tenant:write'])]
    private ?string $moveDate = null;

    #[ORM\ManyToOne(inversedBy: 'tenants')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['tenant:read', 'tenant:write'])]
    private ?Organization $organization = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->leaseTenants = new ArrayCollection(); // ✅ Initialiser la collection
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

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
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

    // ✅ Méthodes pour gérer la collection de leaseTenants
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
            $leaseTenant->setTenant($this);
        }

        return $this;
    }

    public function removeLeaseTenant(LeaseTenant $leaseTenant): static
    {
        if ($this->leaseTenants->removeElement($leaseTenant)) {
            if ($leaseTenant->getTenant() === $this) {
                $leaseTenant->setTenant(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s %s', $this->firstname, $this->lastname);
    }

    // ... (vos propriétés existantes)

    /**
     * ✅ Retourne le bail actif s'il existe
     */
    public function getActiveLease(): ?Lease
    {
        foreach ($this->leaseTenants as $leaseTenant) {
            $lease = $leaseTenant->getLease();
            if ($lease && empty($lease->getEndDate())) {
                return $lease;
            }
        }
        return null;
    }





    /**
     * Retourne l'historique complet des baux avec détails
     */
    #[Groups(['tenant:read'])]
    public function getLeaseHistory(): array
    {
        $history = [];

        foreach ($this->leaseTenants as $leaseTenant) {
            $lease = $leaseTenant->getLease();
            if (!$lease) {
                continue;
            }

            $housing = $lease->getHousing();

            $history[] = [
                // Infos LeaseTenant (pour pouvoir le modifier/supprimer)
                'leaseTenantId' => $leaseTenant->getId(),
                'percentage' => $leaseTenant->getPercentage(),
                'isActive' => $leaseTenant->isActive(),

                // Infos Lease
                'lease' => [
                    'id' => $lease->getId(),
                    'startDate' => $lease->getStartDate()?->format('Y-m-d'),
                    'endDate' => $lease->getEndDate()?->format('Y-m-d'),
                ],

                // Infos Housing
                'housing' => $housing ? [
                    'id' => $housing->getId(),
                    'title' => $housing->getTitle(),
                    'address' => $housing->getAddress(),
                    'city' => $housing->getCity(),
                    'cityCode' => $housing->getCityCode(),
                    'building' => $housing->getBuilding(),
                    'apartmentNumber' => $housing->getApartmentNumber(),
                ] : null,
            ];
        }

        // Trier par date de début décroissante (plus récent en premier)
        usort($history, function ($a, $b) {
            return ($b['lease']['startDate'] ?? '') <=> ($a['lease']['startDate'] ?? '');
        });

        return $history;
    }

    public function getNewHousingId(): ?int
    {
        return $this->newHousingId;
    }

    public function setNewHousingId(?int $newHousingId): static
    {
        $this->newHousingId = $newHousingId;
        return $this;
    }

    public function getMoveDate(): ?string
    {
        return $this->moveDate;
    }

    public function setMoveDate(?string $moveDate): static
    {
        $this->moveDate = $moveDate;
        return $this;
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

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): static
    {
        $this->organization = $organization;
        return $this;
    }
}