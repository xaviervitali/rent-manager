<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository,
        private MailerInterface $mailer,
        private string $mailerFrom,
        private string $frontendUrl
    ) {
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des données
        if (empty($data['email']) || empty($data['password'])) {
            return new JsonResponse(['error' => 'Email et mot de passe requis'], 400);
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Email invalide'], 400);
        }

        if (strlen($data['password']) < 8) {
            return new JsonResponse(['error' => 'Le mot de passe doit contenir au moins 8 caractères'], 400);
        }

        // Vérifier si l'email existe déjà
        $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'Cet email est déjà utilisé'], 400);
        }

        // Créer l'utilisateur
        $user = new User();
        $user->setEmail($data['email']);
        $user->setFirstname($data['firstname'] ?? null);
        $user->setLastname($data['lastname'] ?? null);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        $user->setEmailVerified(false);

        // Générer le token de vérification
        $token = $user->generateEmailVerificationToken();

        $this->em->persist($user);
        $this->em->flush();

        // Envoyer l'email de vérification (ne pas bloquer si ça échoue)
        $emailSent = false;
        try {
            $this->sendVerificationEmail($user, $token);
            $emailSent = true;
        } catch (\Exception $e) {
            error_log('Erreur envoi email: ' . $e->getMessage());
        }

        return new JsonResponse([
            'message' => $emailSent
                ? 'Inscription réussie. Vérifiez votre email pour activer votre compte.'
                : 'Inscription réussie. Vous pouvez vous connecter.',
            'email' => $user->getEmail(),
            'emailSent' => $emailSent
        ], 201);
    }

    #[Route('/api/verify-email', name: 'api_verify_email', methods: ['GET'])]
    public function verifyEmail(Request $request): JsonResponse
    {
        $token = $request->query->get('token');

        if (!$token) {
            return new JsonResponse(['error' => 'Token manquant'], 400);
        }

        $user = $this->userRepository->findOneBy(['emailVerificationToken' => $token]);

        if (!$user) {
            return new JsonResponse(['error' => 'Token invalide'], 400);
        }

        if (!$user->isEmailVerificationTokenValid()) {
            return new JsonResponse(['error' => 'Le lien de vérification a expiré'], 400);
        }

        // Activer le compte
        $user->setEmailVerified(true);
        $user->setEmailVerificationToken(null);
        $user->setEmailVerificationTokenExpiresAt(null);

        $this->em->flush();

        return new JsonResponse(['message' => 'Email vérifié avec succès']);
    }

    #[Route('/api/resend-verification', name: 'api_resend_verification', methods: ['POST'])]
    public function resendVerification(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email'])) {
            return new JsonResponse(['error' => 'Email requis'], 400);
        }

        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            // Pour des raisons de sécurité, on ne révèle pas si l'email existe
            return new JsonResponse(['message' => 'Si cet email existe, un nouveau lien de vérification a été envoyé']);
        }

        if ($user->isEmailVerified()) {
            return new JsonResponse(['error' => 'Cet email est déjà vérifié'], 400);
        }

        // Générer un nouveau token
        $token = $user->generateEmailVerificationToken();
        $this->em->flush();

        // Renvoyer l'email
        $this->sendVerificationEmail($user, $token);

        return new JsonResponse(['message' => 'Un nouveau lien de vérification a été envoyé']);
    }

    private function sendVerificationEmail(User $user, string $token): void
    {
        $verificationUrl =
            $this->frontendUrl .
            '/verify-email?token=' . $token .
            '&email=' . urlencode($user->getEmail());

        $email = (new Email())
            ->from(new Address($this->mailerFrom, 'Rent Manager'))
            ->to($user->getEmail())
            ->subject('Vérifiez votre email - Rent Manager')
            ->html(
                $this->renderVerificationEmailHtml($user, $verificationUrl)
            );

        $this->mailer->send($email);
    }

    private function renderVerificationEmailHtml(User $user, string $verificationUrl): string
    {
        $firstname = $user->getFirstname() ?? 'Utilisateur';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #3B82F6; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
        .button { display: inline-block; background: #3B82F6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Rent Manager</h1>
        </div>
        <div class="content">
            <h2>Bonjour {$firstname},</h2>
            <p>Merci de vous être inscrit sur Rent Manager !</p>
            <p>Pour activer votre compte, veuillez cliquer sur le bouton ci-dessous :</p>
            <p style="text-align: center;">
                <a href="{$verificationUrl}" class="button">Vérifier mon email</a>
            </p>
            <p>Ou copiez ce lien dans votre navigateur :</p>
            <p style="word-break: break-all; background: #eee; padding: 10px; border-radius: 4px;">
                {$verificationUrl}
            </p>
            <p><strong>Ce lien expire dans 24 heures.</strong></p>
            <p>Si vous n'avez pas créé de compte, vous pouvez ignorer cet email.</p>
        </div>
        <div class="footer">
            <p>&copy; Rent Manager - Gestion locative simplifiée</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
