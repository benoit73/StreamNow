<?php

namespace App\Tests\Entity;

use App\Entity\Comment;
use App\Entity\User;
use App\Entity\Video;
use PHPUnit\Framework\TestCase;

class CommentTest extends TestCase
{
    private Comment $comment;

    protected function setUp(): void
    {
        $this->comment = new Comment();
    }

    public function testContentGetterAndSetter(): void
    {
        $content = 'Super vidéo, merci pour le partage !';
        $this->comment->setContent($content);
        
        $this->assertEquals($content, $this->comment->getContent());
    }

    public function testCreatedAtGetterAndSetter(): void
    {
        $date = new \DateTimeImmutable('2024-06-15 14:30:00');
        $this->comment->setCreatedAt($date);
        
        $this->assertEquals($date, $this->comment->getCreatedAt());
    }



    public function testVideoGetterAndSetter(): void
    {
        $video = new Video();
        $this->comment->setVideo($video);
        
        $this->assertSame($video, $this->comment->getVideo());
    }

    public function testCreatedByGetterAndSetter(): void
    {
        $user = new User();
        $user->setName('John Doe');
        
        $this->comment->setCreatedBy($user);
        
        $this->assertSame($user, $this->comment->getCreatedBy());
    }

    public function testParentGetterAndSetter(): void
    {
        $parentComment = new Comment();
        $parentComment->setContent('Commentaire parent');
        
        $this->comment->setParent($parentComment);
        
        $this->assertSame($parentComment, $this->comment->getParent());
    }

    public function testParentCanBeNull(): void
    {
        $this->comment->setParent(null);
        
        $this->assertNull($this->comment->getParent());
    }

    public function testAddAndRemoveReply(): void
    {
        $reply = new Comment();
        $reply->setContent('Réponse au commentaire');
        
        $this->comment->addReply($reply);
        
        $this->assertTrue($this->comment->getReplies()->contains($reply));
        $this->assertSame($this->comment, $reply->getParent());
        
        $this->comment->removeReply($reply);
        
        $this->assertFalse($this->comment->getReplies()->contains($reply));
    }

    public function testAddReplyDoesNotDuplicate(): void
    {
        $reply = new Comment();
        
        $this->comment->addReply($reply);
        $this->comment->addReply($reply);
        
        $this->assertCount(1, $this->comment->getReplies());
    }

    public function testIdIsNullByDefault(): void
    {
        $this->assertNull($this->comment->getId());
    }

    public function testNewCommentHasEmptyRepliesCollection(): void
    {
        $this->assertCount(0, $this->comment->getReplies());
    }

    public function testCommentThreadStructure(): void
    {
        $parentComment = new Comment();
        $parentComment->setContent('Commentaire principal');
        
        $reply1 = new Comment();
        $reply1->setContent('Première réponse');
        
        $reply2 = new Comment();
        $reply2->setContent('Deuxième réponse');
        
        $parentComment->addReply($reply1);
        $parentComment->addReply($reply2);
        
        $this->assertCount(2, $parentComment->getReplies());
        $this->assertNull($parentComment->getParent());
        $this->assertSame($parentComment, $reply1->getParent());
        $this->assertSame($parentComment, $reply2->getParent());
    }
}
