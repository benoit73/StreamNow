<?php

namespace App\Tests\Entity;

use App\Entity\VideoLike;
use App\Entity\Video;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class VideoLikeTest extends TestCase
{
    private VideoLike $videoLike;

    protected function setUp(): void
    {
        $this->videoLike = new VideoLike();
    }

    public function testIsLikeGetterAndSetter(): void
    {
        $this->videoLike->setIsLike(true);
        $this->assertTrue($this->videoLike->isLike());
        
        $this->videoLike->setIsLike(false);
        $this->assertFalse($this->videoLike->isLike());
    }

    public function testVideoGetterAndSetter(): void
    {
        $video = new Video();
        $this->videoLike->setVideo($video);
        
        $this->assertSame($video, $this->videoLike->getVideo());
    }

    public function testOwnerGetterAndSetter(): void
    {
        $user = new User();
        $user->setName('Test User');
        
        $this->videoLike->setOwner($user);
        
        $this->assertSame($user, $this->videoLike->getOwner());
    }

    public function testIdIsNullByDefault(): void
    {
        $this->assertNull($this->videoLike->getId());
    }

    public function testCreateLike(): void
    {
        $user = new User();
        $user->setName('Liker');
        
        $video = new Video();
        $video->setTitle('Test Video');
        
        $this->videoLike->setOwner($user);
        $this->videoLike->setVideo($video);
        $this->videoLike->setIsLike(true);
        
        $this->assertSame($user, $this->videoLike->getOwner());
        $this->assertSame($video, $this->videoLike->getVideo());
        $this->assertTrue($this->videoLike->isLike());
    }

    public function testCreateDislike(): void
    {
        $user = new User();
        $user->setName('Disliker');
        
        $video = new Video();
        $video->setTitle('Test Video');
        
        $this->videoLike->setOwner($user);
        $this->videoLike->setVideo($video);
        $this->videoLike->setIsLike(false);
        
        $this->assertSame($user, $this->videoLike->getOwner());
        $this->assertSame($video, $this->videoLike->getVideo());
        $this->assertFalse($this->videoLike->isLike());
    }

    public function testToggleLikeToDislike(): void
    {
        $this->videoLike->setIsLike(true);
        $this->assertTrue($this->videoLike->isLike());
        
        // Toggle to dislike
        $this->videoLike->setIsLike(false);
        $this->assertFalse($this->videoLike->isLike());
    }

    public function testToggleDislikeToLike(): void
    {
        $this->videoLike->setIsLike(false);
        $this->assertFalse($this->videoLike->isLike());
        
        // Toggle to like
        $this->videoLike->setIsLike(true);
        $this->assertTrue($this->videoLike->isLike());
    }
}
