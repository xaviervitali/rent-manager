<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
        private JWTTokenManagerInterface $jwtManager
    ) {
    }

    public function supports(Request $request): ?bool
    {
        $supports = $request->attributes->get('_route') === 'connect_google_check';
        
        error_log('GoogleAuthenticator::supports() - Route: ' . $request->attributes->get('_route') . ' - Supports: ' . ($supports ? 'YES' : 'NO'));
        
        return $supports;
    }

    public function authenticate(Request $request): Passport
    {
        error_log('GoogleAuthenticator::authenticate() - Starting authentication');
        
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);
        
        error_log('GoogleAuthenticator::authenticate() - Access token fetched');
        
        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                error_log('GoogleAuthenticator::authenticate() - Fetching user from token');
                
                $googleUser = $client->fetchUserFromToken($accessToken);
                $email = $googleUser->getEmail();
                
                error_log('GoogleAuthenticator::authenticate() - Google user email: ' . $email);
                
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                
                if ($existingUser) {
                    error_log('GoogleAuthenticator::authenticate() - Existing user found, ID: ' . $existingUser->getId());
                    return $existingUser;
                }
                
                error_log('GoogleAuthenticator::authenticate() - Creating new user');
                
                $user = new User();
                $user->setEmail($email);
                $user->setRoles(['ROLE_USER']);
                $user->setPassword(bin2hex(random_bytes(32)));
                $user->setCreatedAt(new \DateTimeImmutable());
                $user->setUpdatedAt(new \DateTimeImmutable());
                
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                
                error_log('GoogleAuthenticator::authenticate() - New user created, ID: ' . $user->getId());
                
                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        error_log('=== onAuthenticationSuccess START ===');
        error_log('User authenticated: ' . $user->getUserIdentifier());
        error_log('User ID: ' . $user->getId());

        // Générer le JWT directement ici pour éviter les problèmes
        $jwtToken = $this->jwtManager->create($user);
        error_log('JWT generated: ' . substr($jwtToken, 0, 20) . '...');

        // Rediriger directement vers le frontend avec le token
        $frontendUrl = $_ENV['FRONTEND_URL'];
        $redirectUrl = sprintf('%s/auth/google/callback?token=%s', $frontendUrl, $jwtToken);

        error_log('Redirecting to: ' . $redirectUrl);
        error_log('=== onAuthenticationSuccess END ===');

        return new RedirectResponse($redirectUrl);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        error_log('=== onAuthenticationFailure ===');
        error_log('Error: ' . $exception->getMessage());
        
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());
        
        return new RedirectResponse(
            $this->urlGenerator->generate('google_auth_error', ['error' => $message])
        );
    }
}