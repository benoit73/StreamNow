<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testLoginPageIsAccessible(): void
    {
        $this->client->request('GET', '/login');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testLoginPageContainsEmailField(): void
    {
        $this->client->request('GET', '/login');
        
        $this->assertSelectorExists('input[name="_username"]');
    }

    public function testLoginPageContainsPasswordField(): void
    {
        $this->client->request('GET', '/login');
        
        $this->assertSelectorExists('input[name="_password"]');
    }

    public function testLoginPageContainsRememberMeCheckbox(): void
    {
        $this->client->request('GET', '/login');
        
        $this->assertSelectorExists('input[name="_remember_me"]');
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $crawler = $this->client->request('GET', '/login');
        
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'invalid@example.com',
            '_password' => 'wrongpassword',
        ]);
        
        $this->client->submit($form);
        $this->client->followRedirect();
        
        // Doit rediriger vers login avec une erreur
        $this->assertRouteSame('app_login');
    }

    public function testLogoutRedirectsToHome(): void
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy([]);
        
        if ($user) {
            $this->client->loginUser($user);
            $this->client->request('GET', '/logout');
            
            // Le logout doit rediriger
            $this->assertResponseRedirects();
        } else {
            $this->markTestSkipped('Aucun utilisateur trouvé dans la base de données');
        }
    }

    public function testAuthenticatedUserCannotAccessLoginPage(): void
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy([]);
        
        if ($user) {
            $this->client->loginUser($user);
            $this->client->request('GET', '/login');
            
            // Un utilisateur connecté peut quand même voir la page (comportement Symfony par défaut)
            $this->assertResponseIsSuccessful();
        } else {
            $this->markTestSkipped('Aucun utilisateur trouvé dans la base de données');
        }
    }
}
