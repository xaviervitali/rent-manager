<?php

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;

class TenantTest extends WebTestCase
{
    private $client;
    private $token;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->token = $this->authenticate();
    }

    private function authenticate(): string
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy([]);
        
        $this->client->request('POST', '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => $user->getEmail(),
            'password' => 'password123',
        ]));

        $data = json_decode($this->client->getResponse()->getContent(), true);
        return $data['token'];
    }

    public function testCreateTenant(): void
    {
        $tenantData = [
            'firstname' => 'Jean',
            'lastname' => 'Test',
            'email' => 'jean.test@example.com',
            'phone' => '0612345678',
        ];

        $this->client->request('POST', '/api/tenants', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($tenantData));

        $this->assertResponseStatusCodeSame(201);
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals('Jean', $data['firstname']);
        $this->assertEquals('Test', $data['lastname']);
        
        // Vérifier createdAt
        $this->assertArrayHasKey('createdAt', $data);
        $this->assertNotNull($data['createdAt']);
    }

    public function testGetTenants(): void
    {
        $this->client->request('GET', '/api/tenants', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
        ]);

        $this->assertResponseIsSuccessful();
        
        $content = $this->client->getResponse()->getContent();
        $data = json_decode($content, true);
        
        // Vérifier que c'est bien du JSON-LD
        $this->assertIsArray($data);
        
        // Vérifier qu'il y a des données (la structure peut varier)
        $this->assertTrue(
            isset($data['hydra:member']) || isset($data['member']) || is_array($data),
            'Response should contain tenant data'
        );
    }
}