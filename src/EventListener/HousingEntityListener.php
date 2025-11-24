<?php

namespace App\EventListener;

use App\Entity\Housing;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsEntityListener(event: Events::prePersist, entity: Housing::class)]
class HousingEntityListener
{
    public function __construct(
        private Security $security
    ) {
    }

    public function prePersist(Housing $housing): void
    {
        if ($housing->getUser() === null) {
            $housing->setUser($this->security->getUser());
        }
    }
}