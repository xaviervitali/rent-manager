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
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Housing
    {
        if (!$data instanceof Housing) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $isUpdate = $data->getId() !== null;
        $housingId = $data->getId(); // ✅ Sauvegarder l'ID AVANT toute manipulation

        // ✅ UPDATE : Recharger l'entité existante
        if ($isUpdate) {
            $existingHousing = $this->housingRepository->find($housingId);

            if (!$existingHousing) {
                throw new \RuntimeException('Housing not found');
            }

            // Mettre à jour uniquement les champs modifiables
            $existingHousing->setTitle($data->getTitle());
            $existingHousing->setAddress($data->getAddress());
            $existingHousing->setCityCode($data->getCityCode());
            $existingHousing->setCity($data->getCity());
            $existingHousing->setBuilding($data->getBuilding());
            $existingHousing->setApartmentNumber($data->getApartmentNumber());
            $existingHousing->setNote($data->getNote());

            $data = $existingHousing;
        } else {
            // ✅ CRÉATION : Assigner l'utilisateur connecté
            $data->setUser($this->security->getUser());
        }

        // 🔥 VALIDATION DU DOUBLON
        $existingCriteria = [
            'address' => $data->getAddress(),
            'cityCode' => $data->getCityCode(),
            'city' => $data->getCity(),
            'user' => $data->getUser(), 
            'title' => $data->getTitle()
        ];

        if ($data->getBuilding() !== null) {
            $existingCriteria['building'] = $data->getBuilding();
        }
        if ($data->getApartmentNumber() !== null) {
            $existingCriteria['apartmentNumber'] = $data->getApartmentNumber();
        }

        $existing = $this->housingRepository->findOneBy($existingCriteria);

        if ($existing && $existing->getId() !== $housingId) {
            throw new UnprocessableEntityHttpException('Vous avez déjà enregistré ce logement.');
        }

        
        $data->setCreatedAt($existing?->getCreatedAt() ?? new \DateTimeImmutable()); 
        $data->setUpdatedAt(new \DateTimeImmutable());

        // ✅ Persister l'entité
        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}