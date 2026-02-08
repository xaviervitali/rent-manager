<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\OrganizationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository,
        private OrganizationRepository $organizationRepository
    ) {}

    #[Route('/api/users/{id}/change-password', name: 'api_user_change_password', methods: ['POST'])]
    public function changePassword(int $id, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        // Vérifier que l'utilisateur modifie son propre mot de passe
        if ($currentUser->getId() !== $id) {
            return new JsonResponse(['error' => 'Non autorisé'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['currentPassword']) || empty($data['newPassword'])) {
            return new JsonResponse(['error' => 'Mot de passe actuel et nouveau mot de passe requis'], 400);
        }

        // Vérifier le mot de passe actuel
        if (!$this->passwordHasher->isPasswordValid($currentUser, $data['currentPassword'])) {
            return new JsonResponse(['error' => 'Mot de passe actuel incorrect'], 400);
        }

        if (strlen($data['newPassword']) < 8) {
            return new JsonResponse(['error' => 'Le nouveau mot de passe doit contenir au moins 8 caractères'], 400);
        }

        // Changer le mot de passe
        $currentUser->setPassword($this->passwordHasher->hashPassword($currentUser, $data['newPassword']));
        $this->em->flush();

        return new JsonResponse(['message' => 'Mot de passe modifié avec succès']);
    }

    #[Route('/api/debug/organization-members', name: 'api_debug_org_members', methods: ['GET'])]
    public function debugOrganizationMembers(): JsonResponse
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $userIds = [$currentUser->getId()];
        $organizations = $this->organizationRepository->findByUser($currentUser);

        $debug = [
            'currentUser' => [
                'id' => $currentUser->getId(),
                'email' => $currentUser->getEmail(),
            ],
            'organizationsCount' => count($organizations),
            'organizations' => [],
        ];

        foreach ($organizations as $organization) {
            $orgData = [
                'id' => $organization->getId(),
                'name' => $organization->getName(),
                'membersCount' => $organization->getMembers()->count(),
                'members' => [],
            ];

            foreach ($organization->getMembers() as $member) {
                $memberId = $member->getUser()?->getId();
                $orgData['members'][] = [
                    'userId' => $memberId,
                    'email' => $member->getUser()?->getEmail(),
                    'role' => $member->getRole(),
                ];
                if ($memberId !== null && !in_array($memberId, $userIds, true)) {
                    $userIds[] = $memberId;
                }
            }

            $debug['organizations'][] = $orgData;
        }

        $debug['allowedUserIds'] = $userIds;

        return new JsonResponse($debug);
    }
}
