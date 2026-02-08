<?php

namespace App\Controller;

use App\Entity\Housing;
use App\Entity\HousingEvent;
use App\Entity\User;
use App\Repository\HousingEventRepository;
use App\Repository\HousingRepository;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class HousingEventController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private HousingRepository $housingRepository,
        private HousingEventRepository $eventRepository,
        private OrganizationRepository $organizationRepository
    ) {}

    private function canAccessHousing(User $user, Housing $housing): bool
    {
        $allowedUserIds = [$user->getId()];
        foreach ($this->organizationRepository->findByUser($user) as $organization) {
            foreach ($organization->getMembers() as $member) {
                $memberId = $member->getUser()?->getId();
                if ($memberId !== null && !in_array($memberId, $allowedUserIds, true)) {
                    $allowedUserIds[] = $memberId;
                }
            }
        }
        return in_array($housing->getUser()->getId(), $allowedUserIds, true);
    }

    #[Route('/api/housings/{id}/events', name: 'api_housing_events_list', methods: ['GET'])]
    public function list(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $housing = $this->housingRepository->find($id);
        if (!$housing) {
            return new JsonResponse(['error' => 'Logement non trouvé'], 404);
        }

        if (!$this->canAccessHousing($user, $housing)) {
            return new JsonResponse(['error' => 'Non autorisé'], 403);
        }

        $events = $this->eventRepository->findByHousingOrderedByDate($id);

        $data = array_map(fn(HousingEvent $event) => [
            'id' => $event->getId(),
            'eventDate' => $event->getEventDate()->format('Y-m-d'),
            'title' => $event->getTitle(),
            'description' => $event->getDescription(),
            'category' => $event->getCategory(),
            'author' => [
                'id' => $event->getAuthor()->getId(),
                'email' => $event->getAuthor()->getEmail(),
            ],
            'createdAt' => $event->getCreatedAt()->format('c'),
            'updatedAt' => $event->getUpdatedAt()->format('c'),
        ], $events);

        return new JsonResponse(['events' => $data]);
    }

    #[Route('/api/housings/{id}/events', name: 'api_housing_events_create', methods: ['POST'])]
    public function create(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $housing = $this->housingRepository->find($id);
        if (!$housing) {
            return new JsonResponse(['error' => 'Logement non trouvé'], 404);
        }

        if (!$this->canAccessHousing($user, $housing)) {
            return new JsonResponse(['error' => 'Non autorisé'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['eventDate']) || empty($data['title'])) {
            return new JsonResponse(['error' => 'Date et titre requis'], 400);
        }

        try {
            $eventDate = new \DateTimeImmutable($data['eventDate']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Format de date invalide'], 400);
        }

        $event = new HousingEvent();
        $event->setHousing($housing);
        $event->setEventDate($eventDate);
        $event->setTitle($data['title']);
        $event->setDescription($data['description'] ?? null);
        $event->setCategory($data['category'] ?? null);
        $event->setAuthor($user);

        $this->em->persist($event);
        $this->em->flush();

        return new JsonResponse([
            'message' => 'Événement créé avec succès',
            'event' => [
                'id' => $event->getId(),
                'eventDate' => $event->getEventDate()->format('Y-m-d'),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'category' => $event->getCategory(),
                'author' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                ],
                'createdAt' => $event->getCreatedAt()->format('c'),
            ]
        ], 201);
    }

    #[Route('/api/housings/{housingId}/events/{eventId}', name: 'api_housing_events_update', methods: ['PUT'])]
    public function update(int $housingId, int $eventId, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $housing = $this->housingRepository->find($housingId);
        if (!$housing) {
            return new JsonResponse(['error' => 'Logement non trouvé'], 404);
        }

        if (!$this->canAccessHousing($user, $housing)) {
            return new JsonResponse(['error' => 'Non autorisé'], 403);
        }

        $event = $this->eventRepository->find($eventId);
        if (!$event || $event->getHousing()->getId() !== $housingId) {
            return new JsonResponse(['error' => 'Événement non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['eventDate'])) {
            try {
                $event->setEventDate(new \DateTimeImmutable($data['eventDate']));
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Format de date invalide'], 400);
            }
        }

        if (isset($data['title'])) {
            $event->setTitle($data['title']);
        }

        if (array_key_exists('description', $data)) {
            $event->setDescription($data['description']);
        }

        if (array_key_exists('category', $data)) {
            $event->setCategory($data['category']);
        }

        $this->em->flush();

        return new JsonResponse([
            'message' => 'Événement modifié avec succès',
            'event' => [
                'id' => $event->getId(),
                'eventDate' => $event->getEventDate()->format('Y-m-d'),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'category' => $event->getCategory(),
                'author' => [
                    'id' => $event->getAuthor()->getId(),
                    'email' => $event->getAuthor()->getEmail(),
                ],
                'createdAt' => $event->getCreatedAt()->format('c'),
                'updatedAt' => $event->getUpdatedAt()->format('c'),
            ]
        ]);
    }

    #[Route('/api/housings/{housingId}/events/{eventId}', name: 'api_housing_events_delete', methods: ['DELETE'])]
    public function delete(int $housingId, int $eventId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $housing = $this->housingRepository->find($housingId);
        if (!$housing) {
            return new JsonResponse(['error' => 'Logement non trouvé'], 404);
        }

        if (!$this->canAccessHousing($user, $housing)) {
            return new JsonResponse(['error' => 'Non autorisé'], 403);
        }

        $event = $this->eventRepository->find($eventId);
        if (!$event || $event->getHousing()->getId() !== $housingId) {
            return new JsonResponse(['error' => 'Événement non trouvé'], 404);
        }

        $this->em->remove($event);
        $this->em->flush();

        return new JsonResponse(['message' => 'Événement supprimé avec succès']);
    }
}
