<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Api\ApiDashboardController;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/dashboard/{year}',
            controller: ApiDashboardController::class,
            read: false
        )
    ],
    paginationEnabled: false
)]
final class DashboardAnnualReport
{
    public int $year;
    public array $byHousing = [];
    public array $totals = [];
}
