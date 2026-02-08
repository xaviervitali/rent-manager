<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\HousingRepository;
use App\Service\FiscalCalculatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/fiscal')]
class FiscalController extends AbstractController
{
    public function __construct(
        private readonly FiscalCalculatorService $fiscalCalculator,
        private readonly HousingRepository $housingRepository,
    ) {
    }

    /**
     * Récupère le résultat fiscal pour l'utilisateur connecté et une année donnée
     */
    #[Route('/result/{year}', name: 'api_fiscal_result', methods: ['GET'])]
    public function getFiscalResult(int $year): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $result = $this->fiscalCalculator->calculateForUser($user, $year);

        return new JsonResponse($result);
    }

    /**
     * Récupère le résultat fiscal pour un logement spécifique et une année donnée
     */
    #[Route('/housing/{housingId}/result/{year}', name: 'api_fiscal_housing_result', methods: ['GET'])]
    public function getFiscalResultForHousing(int $housingId, int $year): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $housing = $this->housingRepository->find($housingId);

        if (!$housing) {
            return new JsonResponse(['error' => 'Logement non trouvé'], 404);
        }

        // Vérifier que le logement appartient à l'utilisateur
        if ($housing->getUser()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Accès non autorisé'], 403);
        }

        $result = $this->fiscalCalculator->calculateForHousing($housing, $year);

        return new JsonResponse($result);
    }

    /**
     * Récupère l'historique fiscal sur plusieurs années
     */
    #[Route('/history', name: 'api_fiscal_history', methods: ['GET'])]
    public function getFiscalHistory(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $currentYear = (int) date('Y');
        $startYear = $request->query->getInt('startYear', $currentYear - 5);
        $endYear = $request->query->getInt('endYear', $currentYear);

        $history = $this->fiscalCalculator->getHistoryForUser($user, $startYear, $endYear);

        return new JsonResponse([
            'startYear' => $startYear,
            'endYear' => $endYear,
            'history' => $history,
        ]);
    }

    /**
     * Récupère les données pour le formulaire fiscal 2033
     */
    #[Route('/tax-form/{year}', name: 'api_fiscal_tax_form', methods: ['GET'])]
    public function getTaxFormData(int $year): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $result = $this->fiscalCalculator->calculateForUser($user, $year);

        return new JsonResponse([
            'year' => $year,
            'taxForm' => $result['taxForm'],
            'summary' => $result['summary'],
        ]);
    }
}
