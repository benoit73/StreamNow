<?php

namespace App\Tests\Entity;

use App\Entity\Video;
use App\Entity\User;
use App\Entity\Comment;
use App\Entity\Category;
use App\Entity\VideoLike;
use PHPUnit\Framework\TestCase;

class VideoTest extends TestCase
{
    private Video $video;
    private User $owner;

    protected function setUp(): void
    {
        $this->video = new Video();
        $this->owner = new User();
        $this->owner->setName('Test Owner');
        $this->owner->setEmail('owner@test.com');
    }

    public function testTitleGetterAndSetter(): void
    {
        $title = 'Ma super vidéo';
        $this->video->setTitle($title);
        
        $this->assertEquals($title, $this->video->getTitle());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $description = 'Une description détaillée de la vidéo';
        $this->video->setDescription($description);
        
        $this->assertEquals($description, $this->video->getDescription());
    }

    public function testDescriptionCanBeNull(): void
    {
        $this->video->setDescription(null);
        
        $this->assertNull($this->video->getDescription());
    }

    public function testThumbnailGetterAndSetter(): void
    {
        $thumbnail = 'https://example.com/thumbnail.jpg';
        $this->video->setThumbnail($thumbnail);
        
        $this->assertEquals($thumbnail, $this->video->getThumbnail());
    }

    public function testUrlGetterAndSetter(): void
    {
        $url = 'https://youtube.com/watch?v=abc123';
        $this->video->setUrl($url);
        
        $this->assertEquals($url, $this->video->getUrl());
    }

    public function testViewsGetterAndSetter(): void
    {
        $views = 1500;
        $this->video->setViews($views);
        
        $this->assertEquals($views, $this->video->getViews());
    }



    public function testCreatedAtGetterAndSetter(): void
    {
        $date = new \DateTimeImmutable('2024-06-15');
        $this->video->setCreatedAt($date);
        
        $this->assertEquals($date, $this->video->getCreatedAt());
    }

    public function testOwnerGetterAndSetter(): void
    {
        $this->video->setOwner($this->owner);
        
        $this->assertSame($this->owner, $this->video->getOwner());
    }

    public function testCategoryGetterAndSetter(): void
    {
        $category = new Category();
        $this->video->setCategory($category);
        
        $this->assertSame($category, $this->video->getCategory());
    }

    public function testCategoryCanBeNull(): void
    {
        $this->video->setCategory(null);
        
        $this->assertNull($this->video->getCategory());
    }

    public function testAddAndRemoveComment(): void
    {
        $comment = new Comment();
        
        $this->video->addComment($comment);
        $this->assertTrue($this->video->getComments()->contains($comment));
        $this->assertSame($this->video, $comment->getVideo());
        
        $this->video->removeComment($comment);
        $this->assertFalse($this->video->getComments()->contains($comment));
    }

    public function testAddCommentDoesNotDuplicate(): void
    {
        $comment = new Comment();
        
        $this->video->addComment($comment);
        $this->video->addComment($comment);
        
        $this->assertCount(1, $this->video->getComments());
    }

    public function testAddAndRemoveVideoLike(): void
    {
        $videoLike = new VideoLike();
        
        $this->video->addVideoLike($videoLike);
        $this->assertTrue($this->video->getVideoLikes()->contains($videoLike));
        $this->assertSame($this->video, $videoLike->getVideo());
        
        $this->video->removeVideoLike($videoLike);
        $this->assertFalse($this->video->getVideoLikes()->contains($videoLike));
    }

    public function testIsLikedByUserReturnsFalseWhenUserIsNull(): void
    {
        $this->assertFalse($this->video->isLikedByUser(null));
    }

    public function testIsLikedByUserReturnsFalseWhenUserHasNotLiked(): void
    {
        $user = new User();
        
        $this->assertFalse($this->video->isLikedByUser($user));
    }

    public function testIsLikedByUserReturnsTrueWhenUserHasLiked(): void
    {
        $user = new User();
        
        $like = new VideoLike();
        $like->setIsLike(true);
        $like->setOwner($user);
        
        $this->video->addVideoLike($like);
        
        $this->assertTrue($this->video->isLikedByUser($user));
    }

    public function testIsLikedByUserReturnsFalseWhenUserHasDisliked(): void
    {
        $user = new User();
        
        $dislike = new VideoLike();
        $dislike->setIsLike(false);
        $dislike->setOwner($user);
        
        $this->video->addVideoLike($dislike);
        
        $this->assertFalse($this->video->isLikedByUser($user));
    }

    public function testIsDislikedByUserReturnsFalseWhenUserIsNull(): void
    {
        $this->assertFalse($this->video->isDislikedByUser(null));
    }

    public function testIsDislikedByUserReturnsFalseWhenUserHasNotDisliked(): void
    {
        $user = new User();
        
        $this->assertFalse($this->video->isDislikedByUser($user));
    }

    public function testIsDislikedByUserReturnsTrueWhenUserHasDisliked(): void
    {
        $user = new User();
        
        $dislike = new VideoLike();
        $dislike->setIsLike(false);
        $dislike->setOwner($user);
        
        $this->video->addVideoLike($dislike);
        
        $this->assertTrue($this->video->isDislikedByUser($user));
    }

    public function testIsDislikedByUserReturnsFalseWhenUserHasLiked(): void
    {
        $user = new User();
        
        $like = new VideoLike();
        $like->setIsLike(true);
        $like->setOwner($user);
        
        $this->video->addVideoLike($like);
        
        $this->assertFalse($this->video->isDislikedByUser($user));
    }

    public function testIdIsNullByDefault(): void
    {
        $this->assertNull($this->video->getId());
    }

    public function testNewVideoHasEmptyCollections(): void
    {
        $this->assertCount(0, $this->video->getComments());
        $this->assertCount(0, $this->video->getVideoLikes());
    }
}
