<?php

namespace App\EventListener;

use App\Entity\Imputation;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, entity: Imputation::class)]
class ImputationEntityListener
{
    public function prePersist(Imputation $imputation): void
    {
        // Pour Imputation, lié via Lease
    }
}