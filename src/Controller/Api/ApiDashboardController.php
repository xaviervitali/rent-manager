<?php

namespace App\Controller\Api;

use App\Services\HousingAnnualReportService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class ApiDashboardController
{
    public function __construct(
        private HousingAnnualReportService $reportService
    ) {}

    public function __invoke(int $year): JsonResponse
    {
        return new JsonResponse(
            $this->reportService->getAnnualSummary($year)
        );
    }
}
