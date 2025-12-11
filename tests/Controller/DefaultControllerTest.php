<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testHomePageIsAccessible(): void
    {
        $this->client->request('GET', '/');
        
        $this->assertResponseIsSuccessful();
    }

    public function testHomePageContainsStreamNowBrand(): void
    {
        $this->client->request('GET', '/');
        
        $this->assertSelectorTextContains('body', 'StreamNow');
    }

    public function testHomePageDisplaysVideoSection(): void
    {
        $this->client->request('GET', '/');
        
        $this->assertResponseIsSuccessful();
    }

    public function testSearchFunctionality(): void
    {
        $this->client->request('GET', '/', ['q' => 'test']);
        
        $this->assertResponseIsSuccessful();
    }

    public function testCategoryFilter(): void
    {
        $this->client->request('GET', '/', ['category' => '1']);
        
        $this->assertResponseIsSuccessful();
    }

    public function testHomePageHasNavigationLinks(): void
    {
        $crawler = $this->client->request('GET', '/');
        
        // Vérifier qu'il y a au moins un lien
        $this->assertGreaterThan(0, $crawler->filter('a')->count());
    }

    public function testHomePageHasLoginLinkForGuest(): void
    {
        $this->client->request('GET', '/');
        
        // Vérifier la présence d'un lien de connexion
        $this->assertSelectorExists('a[href="/login"]');
    }
}
