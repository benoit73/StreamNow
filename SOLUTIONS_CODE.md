# 🔧 SOLUTIONS DE CODE - StreamNow

Ce document contient les extraits de code prêts à copier-coller pour résoudre les problèmes identifiés.

---

## 📌 PRIORITÉ HAUTE - À FAIRE EN PREMIER

### 1. Refactoriser like/dislike dans VideoController.php

**Problème:** 60 lignes de code dupliqué (lignes 197-220 et 245-268)

**Solution:**

```php
<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Video;
use App\Entity\VideoLike;
use App\Form\CommentType;
use App\Form\VideoType;
use App\Repository\CommentRepository;
use App\Repository\VideoLikeRepository;
use App\Repository\VideoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/video')]
final class VideoController extends AbstractController
{
    // ... autres méthodes ...

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/{id}/like', name: 'app_video_like', methods: ['POST'])]
    public function like(Request $request, Video $video, EntityManagerInterface $entityManager, VideoLikeRepository $videoLikeRepository): Response
    {
        return $this->handleVideoVote($request, $video, true, $entityManager, $videoLikeRepository);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/{id}/dislike', name: 'app_video_dislike', methods: ['POST'])]
    public function dislike(Request $request, Video $video, EntityManagerInterface $entityManager, VideoLikeRepository $videoLikeRepository): Response
    {
        return $this->handleVideoVote($request, $video, false, $entityManager, $videoLikeRepository);
    }

    private function handleVideoVote(
        Request $request,
        Video $video,
        bool $isLike,
        EntityManagerInterface $entityManager,
        VideoLikeRepository $videoLikeRepository
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $csrfName = $isLike ? 'like' : 'dislike';

        if ($this->isCsrfTokenValid($csrfName . $video->getId(), $request->request->get('_token'))) {
            // Chercher si l'utilisateur a déjà voté
            $existingVote = $videoLikeRepository->findOneBy(['video' => $video, 'owner' => $user]);

            if ($existingVote) {
                if ($existingVote->isLike() === $isLike) {
                    // Déjà voté pareil -> retirer le vote
                    $entityManager->remove($existingVote);
                } else {
                    // Vote contraire -> inverser le vote
                    $existingVote->setIsLike($isLike);
                }
            } else {
                // Nouveau vote
                $vote = new VideoLike();
                $vote->setVideo($video);
                $vote->setOwner($user);
                $vote->setIsLike($isLike);
                $entityManager->persist($vote);
            }

            $entityManager->flush();
        }

        return $this->redirectToReferer($request, $video->getId());
    }

    private function redirectToReferer(Request $request, int $videoId): Response
    {
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_video_show', ['id' => $videoId]);
    }
}
```

---

### 2. Fixer Open Redirect dans UserController.php

**Problème:** Ligne 62 - redirection vers URL non validée

**Solution:**

```php
<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
final class UserController extends AbstractController
{
    // ... autres méthodes ...

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/{id}/subscribe', name: 'app_user_subscribe', methods: ['POST'])]
    public function subscribe(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // On ne peut pas s'abonner à soi-même
        if ($currentUser->getId() === $user->getId()) {
            return $this->redirectToRoute('app_user_profile', ['id' => $user->getId()]);
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('subscribe' . $user->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_user_profile', ['id' => $user->getId()]);
        }

        // Toggle: s'abonner ou se désabonner
        if ($currentUser->getAbonnements()->contains($user)) {
            $currentUser->removeAbonnement($user);
        } else {
            $currentUser->addAbonnement($user);
        }

        $entityManager->flush();

        // Redirection sécurisée vers la page précédente
        return $this->redirectToSafeReferer($request, 'app_user_profile', ['id' => $user->getId()]);
    }

    /**
     * Valide et redirige vers le referer ou une route de fallback
     */
    private function redirectToSafeReferer(
        Request $request,
        string $fallbackRoute,
        array $fallbackParams = []
    ): Response {
        $referer = $request->headers->get('referer');

        if ($referer) {
            // Valider que l'URL appartient au même domaine
            $parsedUrl = parse_url($referer);
            $requestHost = $request->getHost();

            if (isset($parsedUrl['host']) && $parsedUrl['host'] === $requestHost) {
                return $this->redirect($referer);
            }
        }

        return $this->redirectToRoute($fallbackRoute, $fallbackParams);
    }
}
```

---

### 3. Refactoriser Video.php - Code Dupliqué

**Problème:** Lignes 270-301 - isLikedByUser() et isDislikedByUser() dupliquées

**Solution:**

```php
<?php

namespace App\Entity;

use App\Repository\VideoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VideoRepository::class)]
class Video
{
    // ... propriétés existantes ...

    /**
     * Vérifie si un utilisateur a voté (like ou dislike)
     */
    private function hasUserVote(?User $user, bool $isLike): bool
    {
        if (!$user) {
            return false;
        }

        return $this->videoLikes
            ->filter(fn(VideoLike $vl) => 
                $vl->getOwner() === $user && $vl->isLike() === $isLike
            )
            ->count() > 0;
    }

    /**
     * Vérifie si un utilisateur a liké cette vidéo
     */
    public function isLikedByUser(?User $user): bool
    {
        return $this->hasUserVote($user, true);
    }

    /**
     * Vérifie si un utilisateur a disliké cette vidéo
     */
    public function isDislikedByUser(?User $user): bool
    {
        return $this->hasUserVote($user, false);
    }
}
```

---

### 4. Sécuriser VideoLike.php - isLike Nullable

**Problème:** Ligne 13 - property `isLike` peut être null

**Solution:**

```php
<?php

namespace App\Entity;

use App\Repository\VideoLikeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VideoLikeRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_video_user', columns: ['video_id', 'owner_id'])]
class VideoLike
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Indique si c'est un like (true) ou un dislike (false)
     */
    #[ORM\Column]
    private bool $isLike = false;  // ← CHANGÉ: plus de nullable, valeur par défaut

    #[ORM\ManyToOne(inversedBy: 'videoLikes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Video $video = null;

    #[ORM\ManyToOne(inversedBy: 'videoLikes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isLike(): bool
    {
        return $this->isLike;
    }

    public function setIsLike(bool $isLike): static
    {
        $this->isLike = $isLike;

        return $this;
    }

    /**
     * Inverse le vote (like <-> dislike)
     */
    public function toggleVote(): void
    {
        $this->isLike = !$this->isLike;
    }

    public function getVideo(): ?Video
    {
        return $this->video;
    }

    public function setVideo(?Video $video): static
    {
        $this->video = $video;

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
}
```

---

### 5. Améliorer RegistrationFormType.php - Password Security

**Problème:** Lignes 40-45 - mot de passe trop court et pas de validation complexité

**Solution:**

```php
<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition duration-200',
                    'placeholder' => 'Votre nom complet',
                ],
                'label' => 'Nom',
                'label_attr' => ['class' => 'block text-sm font-medium text-gray-300 mb-2'],
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer votre nom'),
                    new Length(
                        min: 2,
                        max: 100,
                        minMessage: 'Votre nom doit contenir au moins {{ limit }} caractères',
                    ),
                ],
            ])
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition duration-200',
                    'placeholder' => 'vous@exemple.com',
                ],
                'label' => 'Adresse email',
                'label_attr' => ['class' => 'block text-sm font-medium text-gray-300 mb-2'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'attr' => [
                        'class' => 'w-full px-4 py-3 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition duration-200',
                        'placeholder' => '••••••••',
                        'autocomplete' => 'new-password',
                    ],
                    'label' => 'Mot de passe',
                    'label_attr' => ['class' => 'block text-sm font-medium text-gray-300 mb-2'],
                ],
                'second_options' => [
                    'attr' => [
                        'class' => 'w-full px-4 py-3 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition duration-200',
                        'placeholder' => '••••••••',
                        'autocomplete' => 'new-password',
                    ],
                    'label' => 'Confirmer le mot de passe',
                    'label_attr' => ['class' => 'block text-sm font-medium text-gray-300 mb-2'],
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer un mot de passe'),
                    new Length(
                        min: 12,  // ← AUGMENTÉ DE 6 À 12
                        max: 4096,
                        minMessage: 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                    ),
                    new Regex(
                        pattern: '/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[!@#$%^&*])/',
                        message: 'Le mot de passe doit contenir une majuscule, une minuscule, un chiffre et un caractère spécial (!@#$%^&*)',
                    ),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => 'J\'accepte les <a href="/conditions-utilisation" target="_blank" class="text-purple-500 hover:underline">conditions d\'utilisation</a>',
                'label_html' => true,
                'label_attr' => ['class' => 'ml-2 text-sm text-gray-300'],
                'attr' => ['class' => 'w-4 h-4 text-purple-600 bg-gray-800 border-gray-700 rounded focus:ring-purple-500 focus:ring-2'],
                'constraints' => [
                    new IsTrue(message: 'Vous devez accepter les conditions d\'utilisation.'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
```

---

## 🟡 PRIORITÉ MOYENNE

### 6. Ajouter Validations à Category.php

```php
<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de la catégorie ne peut pas être vide')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'L\'URL du logo est requise')]
    #[Assert\Url(message: 'L\'URL du logo n\'est pas valide')]
    private ?string $logo = null;

    /**
     * @var Collection<int, Video>
     */
    #[ORM\OneToMany(targetEntity: Video::class, mappedBy: 'category')]
    private Collection $videos;

    public function __construct()
    {
        $this->videos = new ArrayCollection();
    }

    // ... reste des getter/setter ...
}
```

---

### 7. Améliorer Comment.php - Validations

```php
<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le commentaire ne peut pas être vide')]
    #[Assert\Length(
        min: 1,
        max: 2000,
        maxMessage: 'Le commentaire ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $content = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Assert\GreaterThanOrEqual(0)]
    private ?int $likes = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Video $video = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'replies')]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $replies;

    public function __construct()
    {
        $this->replies = new ArrayCollection();
    }

    // ... getters/setters ...

    /**
     * Retourne le nombre de réponses
     */
    public function getRepliesCount(): int
    {
        return $this->replies->count();
    }

    // ... reste du code ...
}
```

---

### 8. Implémenter Security Voter pour les Vidéos

**Créer:** `src/Security/Voter/VideoVoter.php`

```php
<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\Video;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class VideoVoter extends Voter
{
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';
    public const VIEW = 'VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE, self::VIEW]) && $subject instanceof Video;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Si pas authentifié
        if (!$user instanceof User) {
            return self::VIEW === $attribute;  // Tous peuvent voir les vidéos
        }

        /** @var Video $video */
        $video = $subject;

        return match ($attribute) {
            self::EDIT => $this->canEdit($video, $user),
            self::DELETE => $this->canDelete($video, $user),
            self::VIEW => true,  // Tous peuvent voir
            default => false,
        };
    }

    private function canEdit(Video $video, User $user): bool
    {
        return $video->getOwner() === $user;
    }

    private function canDelete(Video $video, User $user): bool
    {
        return $video->getOwner() === $user;
    }
}
```

**Usage dans VideoController.php:**

```php
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/{id}/edit', name: 'app_video_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Video $video, EntityManagerInterface $entityManager): Response
{
    // Remplacer la vérification manuelle par le voter
    $this->denyAccessUnlessGranted('EDIT', $video);

    $form = $this->createForm(VideoType::class, $video);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();
        return $this->redirectToRoute('app_video_show', ['id' => $video->getId()], Response::HTTP_SEE_OTHER);
    }

    return $this->render('video/edit.html.twig', [
        'video' => $video,
        'form' => $form,
    ]);
}
```

---

### 9. Améliorer VideoRepository avec Pagination

```php
<?php

namespace App\Repository;

use App\Entity\Video;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Video>
 */
class VideoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Video::class);
    }

    /**
     * Recherche des vidéos par titre ou description
     * @return Video[]
     */
    public function search(string $query, int $offset = 0, int $limit = 50): array
    {
        return $this->createQueryBuilder('v')
            ->where('LOWER(v.title) LIKE LOWER(:query)')
            ->orWhere('LOWER(v.description) LIKE LOWER(:query)')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('v.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les vidéos des propriétaires spécifiés
     * @param array $owners Liste des utilisateurs
     * @return Video[]
     */
    public function findByOwners(array $owners, int $offset = 0, int $limit = 50): array
    {
        if (empty($owners)) {
            return [];
        }

        return $this->createQueryBuilder('v')
            ->where('v.owner IN (:owners)')
            ->setParameter('owners', $owners)
            ->orderBy('v.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les vidéos les plus populaires
     * @return Video[]
     */
    public function findPopularVideos(int $limit = 10): array
    {
        return $this->createQueryBuilder('v')
            ->orderBy('v.views', 'DESC')
            ->addOrderBy('v.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les vidéos récentes
     * @return Video[]
     */
    public function findRecentVideos(int $limit = 20): array
    {
        return $this->createQueryBuilder('v')
            ->orderBy('v.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
```

---

### 10. Améliorer CommentRepository

```php
<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Video;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * Récupère les commentaires récents d'une vidéo (seulement les racines)
     * @return Comment[]
     */
    public function findRecentByVideo(Video $video, int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.video = :video')
            ->andWhere('c.parent IS NULL')  // Seulement les commentaires racines
            ->setParameter('video', $video)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les réponses à un commentaire
     * @return Comment[]
     */
    public function findReplies(Comment $parent): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.parent = :parent')
            ->setParameter('parent', $parent)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les commentaires les plus likés d'une vidéo
     * @return Comment[]
     */
    public function findMostLikedByVideo(Video $video, int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.video = :video')
            ->andWhere('c.parent IS NULL')  // Seulement racines
            ->setParameter('video', $video)
            ->orderBy('c.likes', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
```

---

## 🟢 PRIORITÉ BASSE

### 11. Ajouter Classe SecurityController - Attribut `final`

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController  // ← Ajouter final
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
```

---

## 📊 RÉSUMÉ DES FICHIERS À CRÉER/MODIFIER

### Fichiers à CRÉER:
- `src/Security/Voter/VideoVoter.php` ✨ NOUVEAU
- `src/Service/VideoVoteService.php` (recommandé - PHASE 2)
- `src/Service/CommentService.php` (recommandé - PHASE 2)

### Fichiers à MODIFIER (Priorités):
1. **HAUTE:** VideoController.php, UserController.php, VideoLike.php, RegistrationFormType.php
2. **MOYENNE:** Video.php, Category.php, Comment.php, VideoRepository.php, CommentRepository.php
3. **BASSE:** SecurityController.php, DefaultController.php, UserRepository.php, tous les Form sauf RegistrationFormType.php

---

**Fin du document des solutions de code.**
