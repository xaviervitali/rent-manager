<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\HousingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use App\State\HousingProcessor;

#[ORM\Entity(repositoryClass: HousingRepository::class)]
#[ORM\HasLifecycleCallbacks]

#[UniqueEntity(
    fields: ['address', 'cityCode', 'city', 'building', 'apartmentNumber', 'user'],
    message: 'Vous avez déjà enregistré ce logement.',
    ignoreNull: true
)]

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(processor: HousingProcessor::class),    // ✅ Processor personnalisé
        new Put(processor: HousingProcessor::class),     // ✅ Processor personnalisé
        new Delete()
    ],
    normalizationContext: ['groups' => ['housing:read']],
    denormalizationContext: ['groups' => ['housing:write']]
)]
class Housing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['housing:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['housing:read', 'housing:write'])]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Groups(['housing:read', 'housing:write'])]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    #[Groups(['housing:read', 'housing:write'])]
    private ?string $cityCode = null;

    #[ORM\Column(length: 255)]
    #[Groups(['housing:read', 'housing:write'])]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['housing:read', 'housing:write'])]
    private ?string $building = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['housing:read', 'housing:write'])]
    private ?string $apartmentNumber = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['housing:read', 'housing:write'])]
    private ?string $note = null;

    #[ORM\Column]
    #[Groups(['housing:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['housing:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'housings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['housing:read'])]
    private ?User $user = null;

    /**
     * @var Collection<int, Lease>
     */
    #[ORM\OneToMany(targetEntity: Lease::class, mappedBy: 'housing', orphanRemoval: true)]
    #[Groups(['housing:read'])]
    private Collection $leases;

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
        $this->leases = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getCityCode(): ?string
    {
        return $this->cityCode;
    }

    public function setCityCode(string $cityCode): static
    {
        $this->cityCode = $cityCode;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getBuilding(): ?string
    {
        return $this->building;
    }

    public function setBuilding(?string $building): static
    {
        $this->building = $building;
        return $this;
    }

    public function getApartmentNumber(): ?string
    {
        return $this->apartmentNumber;
    }

    public function setApartmentNumber(?string $apartmentNumber): static
    {
        $this->apartmentNumber = $apartmentNumber;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
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
            $lease->setHousing($this);
        }
        return $this;
    }

    public function removeLease(Lease $lease): static
    {
        if ($this->leases->removeElement($lease)) {
            if ($lease->getHousing() === $this) {
                $lease->setHousing(null);
            }
        }
        return $this;
    }

}