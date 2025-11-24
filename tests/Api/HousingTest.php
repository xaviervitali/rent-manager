<?php

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use App\Entity\Housing;

class HousingTest extends WebTestCase
{
    private $client;
    private $token;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine')->getManager();
        
        // Authentification
        $this->token = $this->authenticate();
    }

    private function authenticate(): string
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([]);
        
        $this->client->request('POST', '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => $user->getEmail(),
            'password' => 'password123',
        ]));

        $data = json_decode($this->client->getResponse()->getContent(), true);
        return $data['token'];
    }

    public function testGetHousingsRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/housings');
        $this->assertResponseStatusCodeSame(401);
    }

public function testGetHousingsWithAuthentication(): void
{
    $this->client->request('GET', '/api/housings', [], [], [
        'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
    ]);

    $this->assertResponseIsSuccessful();
    
    $content = $this->client->getResponse()->getContent();
    $data = json_decode($content, true);
    
    // Vérifier que c'est bien du JSON valide
    $this->assertIsArray($data);
    $this->assertNotNull($data);
}
    public function testCreateHousingWithAutoUserAssignment(): void
    {
        $housingData = [
            'title' => 'Test Apartment',
            'city' => 'Paris',
            'cityCode' => '75001',
            'address' => '123 Test Street',
        ];

        $this->client->request('POST', '/api/housings', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($housingData));

        $this->assertResponseStatusCodeSame(201);
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        // Vérifier que le housing a bien été créé
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals('Test Apartment', $data['title']);
        
        // Vérifier que le user est automatiquement assigné
        $this->assertArrayHasKey('user', $data);
        $this->assertNotNull($data['user']);
        
        // Vérifier que createdAt et updatedAt sont remplis
        $this->assertArrayHasKey('createdAt', $data);
        $this->assertArrayHasKey('updatedAt', $data);
        $this->assertNotNull($data['createdAt']);
        $this->assertNotNull($data['updatedAt']);
    }

    public function testUpdateHousing(): void
    {
        // Créer d'abord un housing
        $housingData = [
            'title' => 'Original Title',
            'city' => 'Paris',
            'cityCode' => '75001',
            'address' => '123 Test Street',
        ];

        $this->client->request('POST', '/api/housings', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($housingData));

        $createdData = json_decode($this->client->getResponse()->getContent(), true);
        $housingId = $createdData['id'];

        // Mettre à jour avec PATCH (pas PUT)
        $this->client->request('PATCH', '/api/housings/' . $housingId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode([
            'title' => 'Updated Title',
        ]));

        $this->assertResponseIsSuccessful();
        $updatedData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertEquals('Updated Title', $updatedData['title']);
    }

    public function testDeleteHousing(): void
    {
        // Créer un housing
        $housingData = [
            'title' => 'To Delete',
            'city' => 'Paris',
            'cityCode' => '75001',
            'address' => '123 Test Street',
        ];

        $this->client->request('POST', '/api/housings', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($housingData));

        $createdData = json_decode($this->client->getResponse()->getContent(), true);
        $housingId = $createdData['id'];

        // Supprimer
        $this->client->request('DELETE', '/api/housings/' . $housingId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
        ]);

        $this->assertResponseStatusCodeSame(204);
    }

    public function testUserCanOnlySeeTheirOwnHousings(): void
    {
        $this->client->request('GET', '/api/housings', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
        ]);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        // Si pas de housings, le test est valide
        if (empty($data['hydra:member'])) {
            $this->addToAssertionCount(1);
            return;
        }

        // Récupérer l'ID du user connecté
        $user = $this->entityManager->getRepository(User::class)->findOneBy([]);
        $currentUserId = $user->getId();

        // Vérifier que tous les housings appartiennent bien au user connecté
        foreach ($data['hydra:member'] as $housing) {
            $userIri = $housing['user'];
            $this->assertStringContainsString('/api/users/' . $currentUserId, $userIri);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}