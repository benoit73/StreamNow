<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testRegisterPageIsAccessible(): void
    {
        $this->client->request('GET', '/register');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testRegisterPageContainsNameField(): void
    {
        $this->client->request('GET', '/register');
        
        $this->assertSelectorExists('input[name="registration_form[name]"]');
    }

    public function testRegisterPageContainsEmailField(): void
    {
        $this->client->request('GET', '/register');
        
        $this->assertSelectorExists('input[name="registration_form[email]"]');
    }

    public function testRegisterPageContainsPasswordField(): void
    {
        $this->client->request('GET', '/register');
        
        // Le champ password est un type composé avec first/second pour la confirmation
        $this->assertSelectorExists('input[id="registration_form_plainPassword_first"]');
    }

    public function testRegisterPageContainsAgreeTermsCheckbox(): void
    {
        $this->client->request('GET', '/register');
        
        $this->assertSelectorExists('input[name="registration_form[agreeTerms]"]');
    }

    public function testRegisterWithValidData(): void
    {
        $crawler = $this->client->request('GET', '/register');
        
        // Générer un email unique pour éviter les conflits
        $uniqueEmail = 'test_' . uniqid() . '@example.com';
        
        $form = $crawler->selectButton('Créer mon compte')->form([
            'registration_form[name]' => 'Test User',
            'registration_form[email]' => $uniqueEmail,
            'registration_form[plainPassword][first]' => 'Password123!',
            'registration_form[plainPassword][second]' => 'Password123!',
            'registration_form[agreeTerms]' => true,
        ]);
        
        $this->client->submit($form);
        
        // Doit rediriger vers la page d'accueil après inscription
        $this->assertResponseRedirects('/');
        
        // Nettoyer l'utilisateur créé
        $entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $userRepository = $entityManager->getRepository(\App\Entity\User::class);
        $user = $userRepository->findOneBy(['email' => $uniqueEmail]);
        if ($user) {
            $entityManager->remove($user);
            $entityManager->flush();
        }
    }

    public function testRegisterWithExistingEmail(): void
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $existingUser = $userRepository->findOneBy([]);
        
        if ($existingUser) {
            $crawler = $this->client->request('GET', '/register');
            
            $form = $crawler->selectButton('Créer mon compte')->form([
                'registration_form[name]' => 'Duplicate User',
                'registration_form[email]' => $existingUser->getEmail(),
                'registration_form[plainPassword][first]' => 'Password123!',
                'registration_form[plainPassword][second]' => 'Password123!',
                'registration_form[agreeTerms]' => true,
            ]);
            
            $this->client->submit($form);
            
            // Doit rester sur la page d'inscription avec une erreur
            $this->assertResponseIsUnprocessable();
        } else {
            $this->markTestSkipped('Aucun utilisateur existant trouvé');
        }
    }

    public function testRegisterWithoutAgreeingTerms(): void
    {
        $crawler = $this->client->request('GET', '/register');
        
        $form = $crawler->selectButton('Créer mon compte')->form([
            'registration_form[name]' => 'Test User',
            'registration_form[email]' => 'newuser_' . uniqid() . '@example.com',
            'registration_form[plainPassword][first]' => 'Password123!',
            'registration_form[plainPassword][second]' => 'Password123!',
        ]);
        
        // Ne pas cocher la case des CGU
        $this->client->submit($form);
        
        // Doit rester sur la page avec une erreur
        $this->assertResponseIsUnprocessable();
    }

    public function testRegisterWithMismatchedPasswords(): void
    {
        $crawler = $this->client->request('GET', '/register');
        
        $form = $crawler->selectButton('Créer mon compte')->form([
            'registration_form[name]' => 'Test User',
            'registration_form[email]' => 'newuser_' . uniqid() . '@example.com',
            'registration_form[plainPassword][first]' => 'Password123!',
            'registration_form[plainPassword][second]' => 'DifferentPassword!',
            'registration_form[agreeTerms]' => true,
        ]);
        
        $this->client->submit($form);
        
        // Doit rester sur la page avec une erreur de mot de passe
        $this->assertResponseIsUnprocessable();
    }
}
