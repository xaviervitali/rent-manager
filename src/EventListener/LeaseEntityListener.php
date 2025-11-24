<?php

namespace App\EventListener;

use App\Entity\Lease;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, entity: Lease::class)]
class LeaseEntityListener
{
    public function prePersist(Lease $lease): void
    {
        // Pour Lease, pas besoin d'assigner un user directement
        // Il est lié via Housing
    }
}