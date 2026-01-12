<?php

namespace App\Controller;

use App\Entity\Organization;
use App\Entity\OrganizationMember;
use App\Entity\User;
use App\Repository\OrganizationRepository;
use App\Repository\UserRepository;
use App\Service\OrganizationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/organizations')]
#[IsGranted('ROLE_USER')]
class OrganizationController extends AbstractController
{
    public function __construct(
        private OrganizationService $organizationService,
        private OrganizationRepository $organizationRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Récupère les organisations de l'utilisateur connecté
     */
    #[Route('/my', name: 'api_organizations_my', methods: ['GET'], priority: 10)]
    public function myOrganizations(): JsonResponse
    {
        $organizations = $this->organizationService->getCurrentUserOrganizations();

        $data = array_map(function (Organization $org) {
            return [
                'id' => $org->getId(),
                'name' => $org->getName(),
                'description' => $org->getDescription(),
                'isAdmin' => $this->organizationService->isAdmin($org),
                'membersCount' => $org->getMembers()->count(),
            ];
        }, $organizations);

        return $this->json($data);
    }

    /**
     * Crée une nouvelle organisation
     */
    #[Route('', name: 'api_organizations_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['name'])) {
            return $this->json(['error' => 'Le nom est requis'], Response::HTTP_BAD_REQUEST);
        }

        $organization = $this->organizationService->createOrganization(
            $data['name'],
            $data['description'] ?? null
        );

        return $this->json([
            'id' => $organization->getId(),
            'name' => $organization->getName(),
            'description' => $organization->getDescription(),
        ], Response::HTTP_CREATED);
    }

    /**
     * Invite un utilisateur dans une organisation (par email)
     */
    #[Route('/{id}/invite', name: 'api_organizations_invite', methods: ['POST'])]
    public function invite(Organization $organization, Request $request): JsonResponse
    {
        // Vérifier que l'utilisateur est admin
        if (!$this->organizationService->isAdmin($organization)) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['email'])) {
            return $this->json(['error' => 'L\'email est requis'], Response::HTTP_BAD_REQUEST);
        }

        $role = $data['role'] ?? OrganizationMember::ROLE_MEMBER;

        // Chercher l'utilisateur par email
        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            return $this->json([
                'error' => 'Aucun utilisateur trouvé avec cet email'
            ], Response::HTTP_NOT_FOUND);
        }

        // Vérifier si déjà membre
        if ($organization->hasMember($user)) {
            return $this->json([
                'error' => 'Cet utilisateur est déjà membre de l\'organisation'
            ], Response::HTTP_CONFLICT);
        }

        // Ajouter le membre
        $currentUser = $this->getUser();
        $member = $this->organizationService->addMember(
            $organization,
            $user,
            $role,
            $currentUser instanceof User ? $currentUser->getEmail() : null
        );

        return $this->json([
            'id' => $member->getId(),
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
            ],
            'role' => $member->getRole(),
            'roleLabel' => $member->getRoleLabel(),
            'joinedAt' => $member->getJoinedAt()?->format('c'),
        ], Response::HTTP_CREATED);
    }

    /**
     * Liste les membres d'une organisation
     */
    #[Route('/{id}/members', name: 'api_organizations_members', methods: ['GET'])]
    public function members(Organization $organization): JsonResponse
    {
        // Vérifier l'accès
        if (!$this->organizationService->hasAccess($organization)) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $members = $organization->getMembers()->map(function (OrganizationMember $member) {
            $user = $member->getUser();
            return [
                'id' => $member->getId(),
                'user' => [
                    'id' => $user?->getId(),
                    'email' => $user?->getEmail(),
                ],
                'role' => $member->getRole(),
                'roleLabel' => $member->getRoleLabel(),
                'joinedAt' => $member->getJoinedAt()?->format('c'),
                'invitedBy' => $member->getInvitedBy(),
            ];
        })->toArray();

        return $this->json(array_values($members));
    }

    /**
     * Modifie le rôle d'un membre
     */
    #[Route('/{id}/members/{memberId}', name: 'api_organizations_update_member', methods: ['PATCH'])]
    public function updateMember(
        Organization $organization,
        int $memberId,
        Request $request
    ): JsonResponse {
        // Vérifier que l'utilisateur est admin
        if (!$this->organizationService->isAdmin($organization)) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['role'])) {
            return $this->json(['error' => 'Le rôle est requis'], Response::HTTP_BAD_REQUEST);
        }

        // Trouver le membre
        $member = null;
        foreach ($organization->getMembers() as $m) {
            if ($m->getId() === $memberId) {
                $member = $m;
                break;
            }
        }

        if (!$member) {
            return $this->json(['error' => 'Membre non trouvé'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->organizationService->changeRole($organization, $member->getUser(), $data['role']);
        } catch (\LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'id' => $member->getId(),
            'role' => $member->getRole(),
            'roleLabel' => $member->getRoleLabel(),
        ]);
    }

    /**
     * Retire un membre de l'organisation
     */
    #[Route('/{id}/members/{memberId}', name: 'api_organizations_remove_member', methods: ['DELETE'])]
    public function removeMember(Organization $organization, int $memberId): JsonResponse
    {
        // Vérifier que l'utilisateur est admin
        if (!$this->organizationService->isAdmin($organization)) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        // Trouver le membre
        $member = null;
        foreach ($organization->getMembers() as $m) {
            if ($m->getId() === $memberId) {
                $member = $m;
                break;
            }
        }

        if (!$member) {
            return $this->json(['error' => 'Membre non trouvé'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->organizationService->removeMember($organization, $member->getUser());
        } catch (\LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Quitter une organisation
     */
    #[Route('/{id}/leave', name: 'api_organizations_leave', methods: ['POST'])]
    public function leave(Organization $organization): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $this->organizationService->removeMember($organization, $user);
        } catch (\LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['message' => 'Vous avez quitté l\'organisation']);
    }
}
