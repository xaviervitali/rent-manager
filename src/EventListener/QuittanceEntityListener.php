<?php

namespace App\EventListener;

use App\Entity\Quittance;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, entity: Quittance::class)]
class QuittanceEntityListener
{
    public function prePersist(Quittance $quittance): void
    {
        // Pour Quittance, lié via Imputation
    }
}