<?php

namespace App\Controller;

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
        private UserRepository $userRepository
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
}
