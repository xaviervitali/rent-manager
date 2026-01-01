<?php
// src/State/HousingProcessor.php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Housing;
use App\Repository\HousingRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class HousingProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private HousingRepository $housingRepository
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Housing {
        if (!$data instanceof Housing) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        // ✅ Utilisateur depuis le token (CREATE + UPDATE)
        $user = $this->security->getUser();
        if (!$user) {
            throw new \RuntimeException('User not authenticated');
        }

        // ✅ Détection UPDATE fiable (API Platform 3)
        $isUpdate = isset($context['previous_data']);
        $housingId = $data->getId();

        // ✅ UPDATE : travailler sur l’entité managée
        if ($isUpdate) {
            /** @var Housing $existingHousing */
            $existingHousing = $context['previous_data'];

            $existingHousing->setTitle($data->getTitle());
            $existingHousing->setAddress($data->getAddress());
            $existingHousing->setCityCode($data->getCityCode());
            $existingHousing->setCity($data->getCity());
            $existingHousing->setBuilding($data->getBuilding());
            $existingHousing->setApartmentNumber($data->getApartmentNumber());
            $existingHousing->setNote($data->getNote());

            $data = $existingHousing;
        }

        // ✅ TOUJOURS forcer le user
        $data->setUser($user);

        // 🔥 Validation du doublon
        $criteria = [
            'address' => $data->getAddress(),
            'cityCode' => $data->getCityCode(),
            'city' => $data->getCity(),
            'title' => $data->getTitle(),
            'user' => $user,
        ];

        if ($data->getBuilding() !== null) {
            $criteria['building'] = $data->getBuilding();
        }

        if ($data->getApartmentNumber() !== null) {
            $criteria['apartmentNumber'] = $data->getApartmentNumber();
        }

        $existing = $this->housingRepository->findOneBy($criteria);

        if ($existing !== null && $existing->getId() !== $data->getId()) {
            throw new UnprocessableEntityHttpException(
                'Vous avez déjà enregistré ce logement.'
            );
        }

        // 🕒 Dates
        if (!$isUpdate) {
            $data->setCreatedAt(new \DateTimeImmutable());
        }

        $data->setUpdatedAt(new \DateTimeImmutable());

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
