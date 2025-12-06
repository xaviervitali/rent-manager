<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GoogleController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectAction(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect([
                'profile',
                'email'
            ], []);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(Request $request): Response
    {
        // Cette route est interceptée par GoogleAuthenticator
        return new Response('Should not reach here');
    }
    #[Route('/auth/google/success', name: 'google_auth_success')]
    public function authSuccessAction(
        Request $request,
        JWTTokenManagerInterface $jwtManager,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        error_log('=== authSuccessAction START ===');

        // Récupérer l'utilisateur depuis la session
        $session = $request->getSession();
        $userId = $session->get('google_auth_user_id');

        error_log('User ID from session: ' . ($userId ?? 'NULL'));

        if (!$userId) {
            error_log('ERROR: No user ID in session');
            $frontendUrl = $_ENV['FRONTEND_URL'] ?? 'http://localhost:5173';
            $redirectUrl = $frontendUrl . '/login?error=no_user_id';
            error_log('Redirecting to: ' . $redirectUrl);
            return new RedirectResponse($redirectUrl);
        }

        $user = $entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            error_log('ERROR: User not found in database with ID: ' . $userId);
            $frontendUrl = $_ENV['FRONTEND_URL'] ?? 'http://localhost:5173';
            $redirectUrl = $frontendUrl . '/login?error=user_not_found';
            error_log('Redirecting to: ' . $redirectUrl);
            return new RedirectResponse($redirectUrl);
        }

        error_log('User found: ' . $user->getEmail());

        // Nettoyer la session
        $session->remove('google_auth_user_id');

        // Générer le JWT
        $token = $jwtManager->create($user);
        error_log('JWT token generated: ' . substr($token, 0, 20) . '...');

        // Rediriger vers le frontend avec le token
        $frontendUrl = $_ENV['FRONTEND_URL'];
        $redirectUrl = sprintf('%s/auth/google/callback?token=%s', $frontendUrl, $token);

        error_log('Final redirect URL: ' . $redirectUrl);

        return new RedirectResponse($redirectUrl);
    }

    #[Route('/auth/google/error', name: 'google_auth_error')]
    public function authErrorAction(Request $request): RedirectResponse
    {
        $error = $request->query->get('error', 'Unknown error');
        $frontendUrl = $_ENV['FRONTEND_URL'];

        return new RedirectResponse(
            sprintf('%s/auth/google/callback?error=%s', $frontendUrl, urlencode($error))
        );
    }
}