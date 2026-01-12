<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\OrganizationMemberRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: OrganizationMemberRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'unique_user_organization', columns: ['user_id', 'organization_id'])]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Patch(),
        new Delete()
    ],
    normalizationContext: ['groups' => ['organization_member:read']],
    denormalizationContext: ['groups' => ['organization_member:write']]
)]
class OrganizationMember
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';
    public const ROLE_VIEWER = 'viewer';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['organization_member:read', 'organization:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'organizationMemberships')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['organization_member:read', 'organization_member:write', 'organization:read'])]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['organization_member:read', 'organization_member:write'])]
    private ?Organization $organization = null;

    #[ORM\Column(length: 50)]
    #[Groups(['organization_member:read', 'organization_member:write', 'organization:read'])]
    private string $role = self::ROLE_MEMBER;

    #[ORM\Column]
    #[Groups(['organization_member:read'])]
    private ?\DateTimeImmutable $joinedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['organization_member:read', 'organization_member:write'])]
    private ?string $invitedBy = null;

    public function __construct()
    {
        $this->joinedAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function setJoinedAtValue(): void
    {
        if ($this->joinedAt === null) {
            $this->joinedAt = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        if (!in_array($role, [self::ROLE_ADMIN, self::ROLE_MEMBER, self::ROLE_VIEWER])) {
            throw new \InvalidArgumentException('Invalid role');
        }
        $this->role = $role;
        return $this;
    }

    public function getJoinedAt(): ?\DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeImmutable $joinedAt): static
    {
        $this->joinedAt = $joinedAt;
        return $this;
    }

    public function getInvitedBy(): ?string
    {
        return $this->invitedBy;
    }

    public function setInvitedBy(?string $invitedBy): static
    {
        $this->invitedBy = $invitedBy;
        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isMember(): bool
    {
        return $this->role === self::ROLE_MEMBER;
    }

    public function isViewer(): bool
    {
        return $this->role === self::ROLE_VIEWER;
    }

    public function canEdit(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MEMBER]);
    }

    #[Groups(['organization_member:read', 'organization:read'])]
    public function getRoleLabel(): string
    {
        return match($this->role) {
            self::ROLE_ADMIN => 'Administrateur',
            self::ROLE_MEMBER => 'Membre',
            self::ROLE_VIEWER => 'Lecteur',
            default => 'Inconnu',
        };
    }
}
