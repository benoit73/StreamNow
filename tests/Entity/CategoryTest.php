<?php

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Entity\Video;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    private Category $category;

    protected function setUp(): void
    {
        $this->category = new Category();
    }

    public function testNameGetterAndSetter(): void
    {
        $name = 'Gaming';
        $this->category->setName($name);
        
        $this->assertEquals($name, $this->category->getName());
    }

    public function testLogoGetterAndSetter(): void
    {
        $logo = '🎮';
        $this->category->setLogo($logo);
        
        $this->assertEquals($logo, $this->category->getLogo());
    }

    public function testIdIsNullByDefault(): void
    {
        $this->assertNull($this->category->getId());
    }

    public function testNewCategoryHasEmptyVideosCollection(): void
    {
        $this->assertCount(0, $this->category->getVideos());
    }

    public function testAddAndRemoveVideo(): void
    {
        $video = new Video();
        
        $this->category->addVideo($video);
        $this->assertTrue($this->category->getVideos()->contains($video));
        $this->assertSame($this->category, $video->getCategory());
        
        $this->category->removeVideo($video);
        $this->assertFalse($this->category->getVideos()->contains($video));
    }

    public function testAddVideoDoesNotDuplicate(): void
    {
        $video = new Video();
        
        $this->category->addVideo($video);
        $this->category->addVideo($video);
        
        $this->assertCount(1, $this->category->getVideos());
    }

    public function testMultipleVideosInCategory(): void
    {
        $video1 = new Video();
        $video1->setTitle('Video 1');
        
        $video2 = new Video();
        $video2->setTitle('Video 2');
        
        $video3 = new Video();
        $video3->setTitle('Video 3');
        
        $this->category->addVideo($video1);
        $this->category->addVideo($video2);
        $this->category->addVideo($video3);
        
        $this->assertCount(3, $this->category->getVideos());
    }
}
