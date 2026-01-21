<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\OrganizationRepository;
use App\State\OrganizationProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: OrganizationRepository::class)]
#[ORM\Table(name: 'rm_organization')]  // ← Ajoutez cette ligne
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(processor: OrganizationProcessor::class),
        new Put(processor: OrganizationProcessor::class),
        new Patch(processor: OrganizationProcessor::class),
        new Delete()
    ],
    normalizationContext: ['groups' => ['organization:read']],
    denormalizationContext: ['groups' => ['organization:write']]
)]
class Organization
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['organization:read', 'housing:read', 'tenant:read', 'lease:read', 'user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['organization:read', 'organization:write', 'housing:read', 'tenant:read', 'user:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['organization:read', 'organization:write', 'user:read'])]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['organization:read', 'organization:write', 'user:read'])]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['organization:read', 'organization:write', 'user:read'])]
    private ?string $address = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['organization:read', 'organization:write', 'user:read'])]
    private ?string $city = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['organization:read', 'organization:write', 'user:read'])]
    private ?string $cityCode = null;

    #[ORM\Column]
    #[Groups(['organization:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['organization:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, OrganizationMember>
     */
    #[ORM\OneToMany(targetEntity: OrganizationMember::class, mappedBy: 'organization', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['organization:read'])]
    private Collection $members;

#[ORM\Column(type: Types::TEXT, nullable: true)]
#[Groups(['organization:read', 'organization:write', 'housing:read', 'tenant:read', 'lease:read', 'user:read'])]
private ?string $note = null;

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getCityCode(): ?string
    {
        return $this->cityCode;
    }

    public function setCityCode(?string $cityCode): static
    {
        $this->cityCode = $cityCode;
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
     * @return Collection<int, OrganizationMember>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(OrganizationMember $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setOrganization($this);
        }
        return $this;
    }

    public function removeMember(OrganizationMember $member): static
    {
        if ($this->members->removeElement($member)) {
            if ($member->getOrganization() === $this) {
                $member->setOrganization(null);
            }
        }
        return $this;
    }

    /**
     * Vérifie si un utilisateur est membre de l'organisation
     */
    public function hasMember(User $user): bool
    {
        foreach ($this->members as $member) {
            if ($member->getUser() === $user) {
                return true;
            }
        }
        return false;
    }

    /**
     * Récupère le membre correspondant à un utilisateur
     */
    public function getMemberByUser(User $user): ?OrganizationMember
    {
        foreach ($this->members as $member) {
            if ($member->getUser() === $user) {
                return $member;
            }
        }
        return null;
    }

    /**
     * Vérifie si un utilisateur est admin de l'organisation
     */
    public function isAdmin(User $user): bool
    {
        $member = $this->getMemberByUser($user);
        return $member && $member->getRole() === OrganizationMember::ROLE_ADMIN;
    }

    public function __toString(): string
    {
        return $this->name ?? 'Organization';
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
}
