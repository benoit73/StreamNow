<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Video;
use App\Entity\Category;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class VideoControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
    }

    public function testVideoIndexPageIsAccessible(): void
    {
        // Note: /video/ avec slash final redirige vers /video
        $this->client->request('GET', '/video');
        
        $this->assertResponseIsSuccessful();
    }

    public function testVideoShowPageRequiresExistingVideo(): void
    {
        // Récupérer une vidéo existante
        $videoRepository = $this->entityManager->getRepository(Video::class);
        $video = $videoRepository->findOneBy([]);
        
        if ($video) {
            $this->client->request('GET', '/video/' . $video->getId());
            $this->assertResponseIsSuccessful();
        } else {
            $this->markTestSkipped('Aucune vidéo trouvée dans la base de données');
        }
    }

    public function testNewVideoPageRequiresAuthentication(): void
    {
        $this->client->request('GET', '/video/new');
        
        // Doit rediriger vers la page de connexion
        $this->assertResponseRedirects();
    }

    public function testNewVideoPageAccessibleWhenAuthenticated(): void
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy([]);
        
        if ($user) {
            $this->client->loginUser($user);
            $this->client->request('GET', '/video/new');
            
            $this->assertResponseIsSuccessful();
        } else {
            $this->markTestSkipped('Aucun utilisateur trouvé dans la base de données');
        }
    }

    public function testEditVideoPageRequiresAuthentication(): void
    {
        $videoRepository = $this->entityManager->getRepository(Video::class);
        $video = $videoRepository->findOneBy([]);
        
        if ($video) {
            $this->client->request('GET', '/video/' . $video->getId() . '/edit');
            $this->assertResponseRedirects();
        } else {
            $this->markTestSkipped('Aucune vidéo trouvée dans la base de données');
        }
    }

    public function testEditVideoPageAccessibleByOwner(): void
    {
        $videoRepository = $this->entityManager->getRepository(Video::class);
        $video = $videoRepository->findOneBy([]);
        
        if ($video && $video->getOwner()) {
            $this->client->loginUser($video->getOwner());
            $this->client->request('GET', '/video/' . $video->getId() . '/edit');
            
            $this->assertResponseIsSuccessful();
        } else {
            $this->markTestSkipped('Aucune vidéo avec propriétaire trouvée');
        }
    }

    public function testLikeVideoRequiresAuthentication(): void
    {
        $videoRepository = $this->entityManager->getRepository(Video::class);
        $video = $videoRepository->findOneBy([]);
        
        if ($video) {
            $this->client->request('POST', '/video/' . $video->getId() . '/like');
            $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        } else {
            $this->markTestSkipped('Aucune vidéo trouvée dans la base de données');
        }
    }

    public function testDislikeVideoRequiresAuthentication(): void
    {
        $videoRepository = $this->entityManager->getRepository(Video::class);
        $video = $videoRepository->findOneBy([]);
        
        if ($video) {
            $this->client->request('POST', '/video/' . $video->getId() . '/dislike');
            $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        } else {
            $this->markTestSkipped('Aucune vidéo trouvée dans la base de données');
        }
    }

    public function testDeleteVideoRequiresAuthentication(): void
    {
        $videoRepository = $this->entityManager->getRepository(Video::class);
        $video = $videoRepository->findOneBy([]);
        
        if ($video) {
            $this->client->request('POST', '/video/' . $video->getId() . '/delete');
            $this->assertResponseRedirects();
        } else {
            $this->markTestSkipped('Aucune vidéo trouvée dans la base de données');
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
