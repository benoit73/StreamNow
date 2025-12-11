<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\Video;
use App\Entity\Comment;
use App\Entity\VideoLike;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testEmailGetterAndSetter(): void
    {
        $email = 'test@example.com';
        $this->user->setEmail($email);
        
        $this->assertEquals($email, $this->user->getEmail());
    }

    public function testNameGetterAndSetter(): void
    {
        $name = 'John Doe';
        $this->user->setName($name);
        
        $this->assertEquals($name, $this->user->getName());
    }

    public function testPasswordGetterAndSetter(): void
    {
        $password = 'hashed_password_123';
        $this->user->setPassword($password);
        
        $this->assertEquals($password, $this->user->getPassword());
    }

    public function testUserIdentifierReturnsEmail(): void
    {
        $email = 'identifier@example.com';
        $this->user->setEmail($email);
        
        $this->assertEquals($email, $this->user->getUserIdentifier());
    }

    public function testRolesAlwaysContainsRoleUser(): void
    {
        // Par défaut, l'utilisateur doit avoir ROLE_USER
        $roles = $this->user->getRoles();
        
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testSetRolesAddsRoleUser(): void
    {
        $this->user->setRoles(['ROLE_ADMIN']);
        $roles = $this->user->getRoles();
        
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
    }

    public function testRolesAreUnique(): void
    {
        $this->user->setRoles(['ROLE_USER', 'ROLE_USER', 'ROLE_ADMIN']);
        $roles = $this->user->getRoles();
        
        // Compte le nombre d'occurrences de ROLE_USER
        $roleUserCount = array_count_values($roles)['ROLE_USER'];
        $this->assertEquals(1, $roleUserCount);
    }

    public function testCreatedAtGetterAndSetter(): void
    {
        $date = new \DateTimeImmutable('2024-01-01');
        $this->user->setCreatedAt($date);
        
        $this->assertEquals($date, $this->user->getCreatedAt());
    }

    public function testAddAndRemoveVideo(): void
    {
        $video = new Video();
        
        $this->user->addVideo($video);
        $this->assertTrue($this->user->getVideos()->contains($video));
        $this->assertSame($this->user, $video->getOwner());
        
        $this->user->removeVideo($video);
        $this->assertFalse($this->user->getVideos()->contains($video));
    }

    public function testAddVideoDoesNotDuplicate(): void
    {
        $video = new Video();
        
        $this->user->addVideo($video);
        $this->user->addVideo($video); // Ajouter deux fois
        
        $this->assertCount(1, $this->user->getVideos());
    }

    public function testAddAndRemoveComment(): void
    {
        $comment = new Comment();
        
        $this->user->addComment($comment);
        $this->assertTrue($this->user->getComments()->contains($comment));
        $this->assertSame($this->user, $comment->getCreatedBy());
        
        $this->user->removeComment($comment);
        $this->assertFalse($this->user->getComments()->contains($comment));
    }

    public function testAbonnementSystem(): void
    {
        $subscriber = new User();
        $subscriber->setName('Subscriber');
        $subscriber->setEmail('subscriber@test.com');
        
        $creator = new User();
        $creator->setName('Creator');
        $creator->setEmail('creator@test.com');
        
        // Le subscriber s'abonne au creator
        $subscriber->addAbonnement($creator);
        
        $this->assertTrue($subscriber->getAbonnements()->contains($creator));
    }

    public function testRemoveAbonnement(): void
    {
        $subscriber = new User();
        $creator = new User();
        
        $subscriber->addAbonnement($creator);
        $subscriber->removeAbonnement($creator);
        
        $this->assertFalse($subscriber->getAbonnements()->contains($creator));
    }

    public function testAddAndRemoveAbonne(): void
    {
        $subscriber = new User();
        $creator = new User();
        
        $creator->addAbonne($subscriber);
        
        $this->assertTrue($creator->getAbonnes()->contains($subscriber));
        $this->assertTrue($subscriber->getAbonnements()->contains($creator));
        
        $creator->removeAbonne($subscriber);
        
        $this->assertFalse($creator->getAbonnes()->contains($subscriber));
        $this->assertFalse($subscriber->getAbonnements()->contains($creator));
    }

    public function testAddAndRemoveVideoLike(): void
    {
        $videoLike = new VideoLike();
        
        $this->user->addVideoLike($videoLike);
        $this->assertTrue($this->user->getVideoLikes()->contains($videoLike));
        $this->assertSame($this->user, $videoLike->getOwner());
        
        $this->user->removeVideoLike($videoLike);
        $this->assertFalse($this->user->getVideoLikes()->contains($videoLike));
    }

    public function testIdIsNullByDefault(): void
    {
        $this->assertNull($this->user->getId());
    }

    public function testNewUserHasEmptyCollections(): void
    {
        $this->assertCount(0, $this->user->getVideos());
        $this->assertCount(0, $this->user->getComments());
        $this->assertCount(0, $this->user->getAbonnements());
        $this->assertCount(0, $this->user->getAbonnes());
        $this->assertCount(0, $this->user->getVideoLikes());
    }
}
