<?php

namespace App\Service;

use App\Entity\Organization;
use App\Entity\OrganizationMember;
use App\Entity\User;
use App\Repository\OrganizationMemberRepository;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class OrganizationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrganizationRepository $organizationRepository,
        private OrganizationMemberRepository $memberRepository,
        private Security $security
    ) {}

    /**
     * Crée une nouvelle organisation et ajoute le créateur comme admin
     */
    public function createOrganization(string $name, ?string $description = null, ?User $creator = null): Organization
    {
        $creator = $creator ?? $this->security->getUser();

        $organization = new Organization();
        $organization->setName($name);
        $organization->setDescription($description);

        $this->entityManager->persist($organization);

        // Ajouter le créateur comme admin
        if ($creator instanceof User) {
            $member = new OrganizationMember();
            $member->setUser($creator);
            $member->setOrganization($organization);
            $member->setRole(OrganizationMember::ROLE_ADMIN);

            // Associer l'organisation à l'utilisateur
            $creator->setOrganization($organization);

            $this->entityManager->persist($member);
            $organization->addMember($member);
        }

        $this->entityManager->flush();

        return $organization;
    }

    /**
     * Ajoute un utilisateur à une organisation
     */
    public function addMember(
        Organization $organization,
        User $user,
        string $role = OrganizationMember::ROLE_MEMBER,
        ?string $invitedBy = null
    ): OrganizationMember {
        // Vérifier si l'utilisateur est déjà membre
        $existingMember = $this->memberRepository->findByUserAndOrganization($user, $organization);
        if ($existingMember) {
            return $existingMember;
        }

        $member = new OrganizationMember();
        $member->setUser($user);
        $member->setOrganization($organization);
        $member->setRole($role);
        $member->setInvitedBy($invitedBy);

        // Mettre à jour le champ organization de l'utilisateur
        $user->setOrganization($organization);

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        return $member;
    }

    /**
     * Retire un utilisateur d'une organisation
     */
    public function removeMember(Organization $organization, User $user): bool
    {
        $member = $this->memberRepository->findByUserAndOrganization($user, $organization);
        if (!$member) {
            return false;
        }

        // Ne pas supprimer le dernier admin
        if ($member->isAdmin()) {
            $admins = $this->memberRepository->findAdminsByOrganization($organization);
            if (count($admins) <= 1) {
                throw new \LogicException('Cannot remove the last admin of an organization');
            }
        }

        // Retirer l'association organisation de l'utilisateur
        if ($user->getOrganization() === $organization) {
            $user->setOrganization(null);
        }

        $this->entityManager->remove($member);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Change le rôle d'un membre
     */
    public function changeRole(Organization $organization, User $user, string $newRole): ?OrganizationMember
    {
        $member = $this->memberRepository->findByUserAndOrganization($user, $organization);
        if (!$member) {
            return null;
        }

        // Ne pas rétrograder le dernier admin
        if ($member->isAdmin() && $newRole !== OrganizationMember::ROLE_ADMIN) {
            $admins = $this->memberRepository->findAdminsByOrganization($organization);
            if (count($admins) <= 1) {
                throw new \LogicException('Cannot demote the last admin of an organization');
            }
        }

        $member->setRole($newRole);
        $this->entityManager->flush();

        return $member;
    }

    /**
     * Récupère les organisations de l'utilisateur connecté
     *
     * @return Organization[]
     */
    public function getCurrentUserOrganizations(): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return [];
        }

        return $this->organizationRepository->findByUser($user);
    }

    /**
     * Vérifie si l'utilisateur connecté a accès à une organisation
     */
    public function hasAccess(Organization $organization): bool
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $this->memberRepository->isMember($user, $organization);
    }

    /**
     * Vérifie si l'utilisateur connecté est admin d'une organisation
     */
    public function isAdmin(Organization $organization): bool
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $this->memberRepository->isAdmin($user, $organization);
    }

    /**
     * Récupère l'organisation "par défaut" de l'utilisateur (la première)
     * Utile pour les requêtes qui nécessitent une organisation
     */
    public function getDefaultOrganization(): ?Organization
    {
        $organizations = $this->getCurrentUserOrganizations();
        return $organizations[0] ?? null;
    }

    /**
     * Crée une organisation personnelle pour un nouvel utilisateur
     */
    public function createPersonalOrganization(User $user): Organization
    {
        $name = sprintf('%s %s', $user->getFirstname() ?? '', $user->getLastname() ?? '');
        $name = trim($name) ?: $user->getEmail();

        return $this->createOrganization($name . ' - Personnel', 'Organisation personnelle', $user);
    }
}
