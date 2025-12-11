<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
    }

    public function testUserProfilePageIsAccessible(): void
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy([]);
        
        if ($user) {
            $this->client->request('GET', '/user/' . $user->getId());
            $this->assertResponseIsSuccessful();
        } else {
            $this->markTestSkipped('Aucun utilisateur trouvé dans la base de données');
        }
    }

    public function testUserProfileDisplaysUserName(): void
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy([]);
        
        if ($user) {
            $this->client->request('GET', '/user/' . $user->getId());
            $this->assertSelectorTextContains('body', $user->getName());
        } else {
            $this->markTestSkipped('Aucun utilisateur trouvé dans la base de données');
        }
    }

    public function testSubscribeRequiresAuthentication(): void
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy([]);
        
        if ($user) {
            $this->client->request('POST', '/user/' . $user->getId() . '/subscribe');
            $this->assertResponseRedirects();
        } else {
            $this->markTestSkipped('Aucun utilisateur trouvé dans la base de données');
        }
    }

    public function testCannotSubscribeToSelf(): void
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy([]);
        
        if ($user) {
            $this->client->loginUser($user);
            
            $csrfToken = $this->client->getContainer()->get('security.csrf.token_manager')
                ->getToken('subscribe' . $user->getId())->getValue();
            
            $this->client->request('POST', '/user/' . $user->getId() . '/subscribe', [
                '_token' => $csrfToken
            ]);
            
            // Vérifie que l'utilisateur n'est pas abonné à lui-même
            $this->entityManager->refresh($user);
            $this->assertFalse($user->getAbonnements()->contains($user));
        } else {
            $this->markTestSkipped('Aucun utilisateur trouvé dans la base de données');
        }
    }

    public function testSubscribeToAnotherUser(): void
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $users = $userRepository->findAll();
        
        if (count($users) >= 2) {
            $subscriber = $users[0];
            $creator = $users[1];
            
            // S'assurer qu'ils ne sont pas déjà abonnés
            if ($subscriber->getAbonnements()->contains($creator)) {
                $subscriber->removeAbonnement($creator);
                $this->entityManager->flush();
            }
            
            $this->client->loginUser($subscriber);
            
            $csrfToken = $this->client->getContainer()->get('security.csrf.token_manager')
                ->getToken('subscribe' . $creator->getId())->getValue();
            
            $this->client->request('POST', '/user/' . $creator->getId() . '/subscribe', [
                '_token' => $csrfToken
            ]);
            
            $this->assertResponseRedirects();
        } else {
            $this->markTestSkipped('Pas assez d\'utilisateurs pour ce test');
        }
    }

    public function testInvalidUserProfileReturns404(): void
    {
        $this->client->request('GET', '/user/999999999');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
