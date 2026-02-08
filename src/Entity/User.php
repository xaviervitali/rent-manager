<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'rm_user')]  // ← Ajoutez cette ligne
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Put(),
        new Patch()
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']]
)]

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(['user:read'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;


    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Housing>
     */
    #[ORM\OneToMany(targetEntity: Housing::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $housings;

    /**
     * @var Collection<int, Tenant>
     */
    #[ORM\OneToMany(targetEntity: Tenant::class, mappedBy: 'user')]
    private Collection $tenants;

    /**
     * @var Collection<int, Lease>
     */
    #[ORM\OneToMany(targetEntity: Lease::class, mappedBy: 'user')]
    private Collection $leases;

    /**
     * @var Collection<int, OrganizationMember>
     */
    #[ORM\OneToMany(targetEntity: OrganizationMember::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $organizationMemberships;

    private ?string $cityCode = null;

    #[ORM\Column(type: 'boolean')]
    private bool $emailVerified = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailVerificationToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $emailVerificationTokenExpiresAt = null;

    #[ORM\Column(length: 6, nullable: true)]
    private ?string $passwordResetCode = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $passwordResetCodeExpiresAt = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?Organization $organization = null;

    #[ORM\Column(length: 20, options: ['default' => 'password'])]
    #[Groups(['user:read'])]
    private string $authProvider = 'password';

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
    public function __construct()
    {
        $this->housings = new ArrayCollection();
        $this->tenants = new ArrayCollection();
        $this->leases = new ArrayCollection();
        $this->organizationMemberships = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);
        
        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
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
     * @return Collection<int, Housing>
     */
    public function getHousings(): Collection
    {
        return $this->housings;
    }

    public function addHousing(Housing $housing): static
    {
        if (!$this->housings->contains($housing)) {
            $this->housings->add($housing);
            $housing->setUser($this);
        }

        return $this;
    }

    public function removeHousing(Housing $housing): static
    {
        if ($this->housings->removeElement($housing)) {
            // set the owning side to null (unless already changed)
            if ($housing->getUser() === $this) {
                $housing->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Tenant>
     */
    public function getTenants(): Collection
    {
        return $this->tenants;
    }

    public function addTenant(Tenant $tenant): static
    {
        if (!$this->tenants->contains($tenant)) {
            $this->tenants->add($tenant);
            $tenant->setUser($this);
        }

        return $this;
    }

    public function removeTenant(Tenant $tenant): static
    {
        if ($this->tenants->removeElement($tenant)) {
            // set the owning side to null (unless already changed)
            if ($tenant->getUser() === $this) {
                $tenant->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Lease>
     */
    public function getLeases(): Collection
    {
        return $this->leases;
    }

    public function addLease(Lease $lease): static
    {
        if (!$this->leases->contains($lease)) {
            $this->leases->add($lease);
            $lease->setUser($this);
        }

        return $this;
    }

    public function removeLease(Lease $lease): static
    {
        if ($this->leases->removeElement($lease)) {
            // set the owning side to null (unless already changed)
            if ($lease->getUser() === $this) {
                $lease->setUser(null);
            }
        }

        return $this;
    }
    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function setEmailVerified(bool $emailVerified): static
    {
        $this->emailVerified = $emailVerified;

        return $this;
    }

    public function getEmailVerificationToken(): ?string
    {
        return $this->emailVerificationToken;
    }

    public function setEmailVerificationToken(?string $token): static
    {
        $this->emailVerificationToken = $token;

        return $this;
    }

    public function getEmailVerificationTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->emailVerificationTokenExpiresAt;
    }

    public function setEmailVerificationTokenExpiresAt(?\DateTimeImmutable $expiresAt): static
    {
        $this->emailVerificationTokenExpiresAt = $expiresAt;

        return $this;
    }

    public function isEmailVerificationTokenValid(): bool
    {
        if (!$this->emailVerificationToken || !$this->emailVerificationTokenExpiresAt) {
            return false;
        }
        return $this->emailVerificationTokenExpiresAt > new \DateTimeImmutable();
    }

    public function generateEmailVerificationToken(): string
    {
        $this->emailVerificationToken = bin2hex(random_bytes(32));
        $this->emailVerificationTokenExpiresAt = new \DateTimeImmutable('+24 hours');
        return $this->emailVerificationToken;
    }

    /**
     * @return Collection<int, OrganizationMember>
     */
    public function getOrganizationMemberships(): Collection
    {
        return $this->organizationMemberships;
    }

    public function addOrganizationMembership(OrganizationMember $membership): static
    {
        if (!$this->organizationMemberships->contains($membership)) {
            $this->organizationMemberships->add($membership);
            $membership->setUser($this);
        }
        return $this;
    }

    public function removeOrganizationMembership(OrganizationMember $membership): static
    {
        if ($this->organizationMemberships->removeElement($membership)) {
            if ($membership->getUser() === $this) {
                $membership->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Organization>
     */
    public function getOrganizations(): Collection
    {
        return $this->organizationMemberships->map(fn(OrganizationMember $m) => $m->getOrganization());
    }

    /**
     * Vérifie si l'utilisateur est membre d'une organisation
     */
    public function isMemberOf(Organization $organization): bool
    {
        foreach ($this->organizationMemberships as $membership) {
            if ($membership->getOrganization() === $organization) {
                return true;
            }
        }
        return false;
    }

    /**
     * Vérifie si l'utilisateur est admin d'une organisation
     */
    public function isAdminOf(Organization $organization): bool
    {
        foreach ($this->organizationMemberships as $membership) {
            if ($membership->getOrganization() === $organization && $membership->isAdmin()) {
                return true;
            }
        }
        return false;
    }

    public function getOrganization(): ?Organization
    {
        // Si le champ direct est défini, l'utiliser
        if ($this->organization !== null) {
            return $this->organization;
        }

        // Sinon, retourner la première organisation via les memberships
        $firstMembership = $this->organizationMemberships->first();
        return $firstMembership ? $firstMembership->getOrganization() : null;
    }

    public function setOrganization(?Organization $organization): static
    {
        $this->organization = $organization;
        return $this;
    }

    public function getPasswordResetCode(): ?string
    {
        return $this->passwordResetCode;
    }

    public function setPasswordResetCode(?string $code): static
    {
        $this->passwordResetCode = $code;
        return $this;
    }

    public function getPasswordResetCodeExpiresAt(): ?\DateTimeImmutable
    {
        return $this->passwordResetCodeExpiresAt;
    }

    public function setPasswordResetCodeExpiresAt(?\DateTimeImmutable $expiresAt): static
    {
        $this->passwordResetCodeExpiresAt = $expiresAt;
        return $this;
    }

    public function isPasswordResetCodeValid(): bool
    {
        if (!$this->passwordResetCode || !$this->passwordResetCodeExpiresAt) {
            return false;
        }
        return $this->passwordResetCodeExpiresAt > new \DateTimeImmutable();
    }

    public function generatePasswordResetCode(): string
    {
        $this->passwordResetCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->passwordResetCodeExpiresAt = new \DateTimeImmutable('+15 minutes');
        return $this->passwordResetCode;
    }

    public function getAuthProvider(): string
    {
        return $this->authProvider;
    }

    public function setAuthProvider(string $authProvider): static
    {
        $this->authProvider = $authProvider;
        return $this;
    }
}
