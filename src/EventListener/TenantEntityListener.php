<?php

namespace App\EventListener;

use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, entity: Tenant::class)]
class TenantEntityListener
{
    public function prePersist(Tenant $tenant): void
    {
        // Pour Tenant, pas besoin d'assigner un user
        // Juste les dates sont gérées par les Lifecycle Callbacks
    }
}