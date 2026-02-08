<?php

namespace App\Controller;

use App\Entity\Housing;
use App\Entity\HousingDocument;
use App\Entity\User;
use App\Repository\HousingDocumentRepository;
use App\Repository\HousingRepository;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class HousingDocumentController extends AbstractController
{
    private const QUOTA_BYTES = 100 * 1024 * 1024; // 100 Mo
    private const UPLOAD_DIR = 'uploads/housing';

    public function __construct(
        private EntityManagerInterface $em,
        private HousingRepository $housingRepository,
        private HousingDocumentRepository $documentRepository,
        private SluggerInterface $slugger,
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

    #[Route('/api/housings/{id}/documents', name: 'api_housing_documents_list', methods: ['GET'])]
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

        $documents = $this->documentRepository->findBy(['housing' => $housing], ['createdAt' => 'DESC']);
        $totalSize = $this->documentRepository->getTotalSizeByHousing($id);

        $data = array_map(fn(HousingDocument $doc) => [
            'id' => $doc->getId(),
            'originalFilename' => $doc->getOriginalFilename(),
            'mimeType' => $doc->getMimeType(),
            'fileSize' => $doc->getFileSize(),
            'description' => $doc->getDescription(),
            'createdAt' => $doc->getCreatedAt()->format('c'),
        ], $documents);

        return new JsonResponse([
            'documents' => $data,
            'totalSize' => $totalSize,
            'quota' => self::QUOTA_BYTES,
            'remainingQuota' => self::QUOTA_BYTES - $totalSize,
        ]);
    }

    #[Route('/api/housings/{id}/documents', name: 'api_housing_documents_upload', methods: ['POST'])]
    public function upload(int $id, Request $request): JsonResponse
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

        $file = $request->files->get('file');
        if (!$file) {
            return new JsonResponse(['error' => 'Aucun fichier envoyé'], 400);
        }

        $fileSize = $file->getSize();
        $currentTotalSize = $this->documentRepository->getTotalSizeByHousing($id);

        if ($currentTotalSize + $fileSize > self::QUOTA_BYTES) {
            $remaining = self::QUOTA_BYTES - $currentTotalSize;
            return new JsonResponse([
                'error' => 'Quota dépassé',
                'remainingQuota' => $remaining,
                'fileSize' => $fileSize
            ], 400);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $extension = $file->guessExtension() ?? $file->getClientOriginalExtension();
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

        $uploadPath = $this->getParameter('kernel.project_dir') . '/var/' . self::UPLOAD_DIR . '/' . $housing->getId();

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $file->move($uploadPath, $newFilename);

        $document = new HousingDocument();
        $document->setHousing($housing);
        $document->setOriginalFilename($file->getClientOriginalName());
        $document->setStoredFilename($newFilename);
        $document->setMimeType($file->getClientMimeType());
        $document->setFileSize($fileSize);
        $document->setDescription($request->request->get('description'));
        $document->setUploadedBy($user);

        $this->em->persist($document);
        $this->em->flush();

        return new JsonResponse([
            'message' => 'Document uploadé avec succès',
            'document' => [
                'id' => $document->getId(),
                'originalFilename' => $document->getOriginalFilename(),
                'mimeType' => $document->getMimeType(),
                'fileSize' => $document->getFileSize(),
                'description' => $document->getDescription(),
                'createdAt' => $document->getCreatedAt()->format('c'),
            ]
        ], 201);
    }

    #[Route('/api/housings/{housingId}/documents/{documentId}', name: 'api_housing_documents_download', methods: ['GET'])]
    public function download(int $housingId, int $documentId): BinaryFileResponse|JsonResponse
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

        $document = $this->documentRepository->find($documentId);
        if (!$document || $document->getHousing()->getId() !== $housingId) {
            return new JsonResponse(['error' => 'Document non trouvé'], 404);
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/var/' . self::UPLOAD_DIR . '/' . $housingId . '/' . $document->getStoredFilename();

        if (!file_exists($filePath)) {
            return new JsonResponse(['error' => 'Fichier non trouvé sur le serveur'], 404);
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $document->getOriginalFilename()
        );

        return $response;
    }

    #[Route('/api/housings/{housingId}/documents/{documentId}', name: 'api_housing_documents_delete', methods: ['DELETE'])]
    public function delete(int $housingId, int $documentId): JsonResponse
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

        $document = $this->documentRepository->find($documentId);
        if (!$document || $document->getHousing()->getId() !== $housingId) {
            return new JsonResponse(['error' => 'Document non trouvé'], 404);
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/var/' . self::UPLOAD_DIR . '/' . $housingId . '/' . $document->getStoredFilename();

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $this->em->remove($document);
        $this->em->flush();

        return new JsonResponse(['message' => 'Document supprimé avec succès']);
    }
}
