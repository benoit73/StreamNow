<?php

namespace App\Entity;

use App\Repository\VideoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VideoRepository::class)]
class Video
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Url]
    private ?string $thumbnail = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Url]
    private ?string $url = null;

    #[ORM\Column]
    private ?int $views = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'videos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'video', orphanRemoval: true, cascade: ['remove'])]
    private Collection $comments;

    #[ORM\ManyToOne(inversedBy: 'videos')]
    private ?Category $category = null;

    /**
     * @var Collection<int, VideoLike>
     */
    #[ORM\OneToMany(targetEntity: VideoLike::class, mappedBy: 'video', orphanRemoval: true)]
    private Collection $videoLikes;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->videoLikes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(string $thumbnail): static
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getViews(): ?int
    {
        return $this->views;
    }

    public function setViews(int $views): static
    {
        $this->views = $views;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setVideo($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getVideo() === $this) {
                $comment->setVideo(null);
            }
        }

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, VideoLike>
     */
    public function getVideoLikes(): Collection
    {
        return $this->videoLikes;
    }

    public function addVideoLike(VideoLike $videoLike): static
    {
        if (!$this->videoLikes->contains($videoLike)) {
            $this->videoLikes->add($videoLike);
            $videoLike->setVideo($this);
        }

        return $this;
    }

    public function removeVideoLike(VideoLike $videoLike): static
    {
        if ($this->videoLikes->removeElement($videoLike)) {
            // set the owning side to null (unless already changed)
            if ($videoLike->getVideo() === $this) {
                $videoLike->setVideo(null);
            }
        }

        return $this;
    }

    /**
     * Compte le nombre de likes (basé sur VideoLike)
     */
    public function getLikesCount(): int
    {
        return $this->videoLikes->filter(fn(VideoLike $vl) => $vl->isLike())->count();
    }

    /**
     * Compte le nombre de dislikes (basé sur VideoLike)
     */
    public function getDislikesCount(): int
    {
        return $this->videoLikes->filter(fn(VideoLike $vl) => !$vl->isLike())->count();
    }



    /**
     * Vérifie si un utilisateur a liké cette vidéo
     */
    public function isLikedByUser(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        foreach ($this->videoLikes as $videoLike) {
            if ($videoLike->getOwner() === $user && $videoLike->isLike()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si un utilisateur a disliké cette vidéo
     */
    public function isDislikedByUser(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        foreach ($this->videoLikes as $videoLike) {
            if ($videoLike->getOwner() === $user && !$videoLike->isLike()) {
                return true;
            }
        }

        return false;
    }
}
