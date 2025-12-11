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
    #[Route(name: 'app_video_index', methods: ['GET'])]
    public function index(VideoRepository $videoRepository): Response
    {
        return $this->render('video/index.html.twig', [
            'videos' => $videoRepository->findAll(),
        ]);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/new', name: 'app_video_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {

        $video = new Video();
        $form = $this->createForm(VideoType::class, $video);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $video->setOwner($this->getUser());
            $video->setCreatedAt(new \DateTimeImmutable());
            $video->setViews(0);
            
            $entityManager->persist($video);
            $entityManager->flush();

            return $this->redirectToRoute('app_video_show', ['id' => $video->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('video/new.html.twig', [
            'video' => $video,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_video_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Video $video, EntityManagerInterface $entityManager, CommentRepository $commentRepository): Response
    {
        // Incrémenter les vues (seulement en GET)
        if ($request->isMethod('GET')) {
            $video->setViews($video->getViews() + 1);
            $entityManager->flush();
        }

        // Formulaire de commentaire principal
        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);
        
        // Traitement POST
        if ($request->isMethod('POST') && $this->getUser()) {
            // Vérifier si c'est une réponse (comment_reply) ou un commentaire principal (comment_form)
            $replyData = $request->request->all('comment_reply');
            $mainFormData = $request->request->all('comment_form');
            
            if ($replyData && !empty($replyData['content'])) {
                // Traitement d'une RÉPONSE à un commentaire
                $submittedToken = $replyData['_token'] ?? '';
                if ($this->isCsrfTokenValid('comment_reply', $submittedToken)) {
                    $reply = new Comment();
                    $reply->setContent($replyData['content']);
                    $reply->setVideo($video);
                    $reply->setCreatedBy($this->getUser());
                    $reply->setCreatedAt(new \DateTimeImmutable());

                    $parentId = $replyData['parentId'] ?? null;
                    if ($parentId) {
                        $parentComment = $commentRepository->find($parentId);
                        if ($parentComment && $parentComment->getVideo()->getId() === $video->getId()) {
                            $reply->setParent($parentComment);
                        }
                    }

                    $entityManager->persist($reply);
                    $entityManager->flush();

                    return $this->redirectToRoute('app_video_show', [
                        'id' => $video->getId(),
                        '_fragment' => 'comment-' . $parentId
                    ]);
                }
            } elseif ($mainFormData && !empty($mainFormData['content'])) {
                // Traitement d'un COMMENTAIRE PRINCIPAL
                $commentForm->handleRequest($request);
                
                if ($commentForm->isSubmitted() && $commentForm->isValid()) {
                    $comment->setVideo($video);
                    $comment->setCreatedBy($this->getUser());
                    $comment->setCreatedAt(new \DateTimeImmutable());

                    $entityManager->persist($comment);
                    $entityManager->flush();

                    return $this->redirectToRoute('app_video_show', [
                        'id' => $video->getId(),
                        '_fragment' => 'comment-' . $comment->getId()
                    ]);
                }
            }
        }

        // Récupérer uniquement les commentaires racines (sans parent) avec leurs réponses
        $comments = $commentRepository->findBy(
            ['video' => $video, 'parent' => null],
            ['createdAt' => 'DESC']
        );

        return $this->render('video/show.html.twig', [
            'video' => $video,
            'commentForm' => $commentForm,
            'comments' => $comments,
        ]);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/{id}/edit', name: 'app_video_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Video $video, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est connecté et propriétaire de la vidéo
        
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if ($user->getId() !== $video->getOwner()->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette vidéo.');
        }

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

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/{id}/delete', name: 'app_video_delete', methods: ['POST'])]
    public function delete(Request $request, Video $video, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est connecté et propriétaire de la vidéo
        
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if ($user->getId() !== $video->getOwner()->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette vidéo.');
        }

        if ($this->isCsrfTokenValid('delete'.$video->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($video);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_profile', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/{id}/like', name: 'app_video_like', methods: ['POST'])]
    public function like(Request $request, Video $video, EntityManagerInterface $entityManager, VideoLikeRepository $videoLikeRepository): Response
    {

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('like'.$video->getId(), $request->request->get('_token'))) {
            // Chercher si l'utilisateur a déjà voté
            $existingVote = $videoLikeRepository->findOneBy(['video' => $video, 'owner' => $user]);

            if ($existingVote) {
                if ($existingVote->isLike()) {
                    // Déjà liké -> on retire le like
                    $entityManager->remove($existingVote);
                } else {
                    // Était un dislike -> on le transforme en like
                    $existingVote->setIsLike(true);
                }
            } else {
                // Nouveau vote
                $vote = new VideoLike();
                $vote->setVideo($video);
                $vote->setOwner($user);
                $vote->setIsLike(true);
                $entityManager->persist($vote);
            }

            $entityManager->flush();
        }

        // Rediriger vers la page d'où vient l'utilisateur
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_video_show', ['id' => $video->getId()]);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/{id}/dislike', name: 'app_video_dislike', methods: ['POST'])]
    public function dislike(Request $request, Video $video, EntityManagerInterface $entityManager, VideoLikeRepository $videoLikeRepository): Response
    {

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('dislike'.$video->getId(), $request->request->get('_token'))) {
            // Chercher si l'utilisateur a déjà voté
            $existingVote = $videoLikeRepository->findOneBy(['video' => $video, 'owner' => $user]);

            if ($existingVote) {
                if (!$existingVote->isLike()) {
                    // Déjà disliké -> on retire le dislike
                    $entityManager->remove($existingVote);
                } else {
                    // Était un like -> on le transforme en dislike
                    $existingVote->setIsLike(false);
                }
            } else {
                // Nouveau vote
                $vote = new VideoLike();
                $vote->setVideo($video);
                $vote->setOwner($user);
                $vote->setIsLike(false);
                $entityManager->persist($vote);
            }

            $entityManager->flush();
        }

        // Rediriger vers la page d'où vient l'utilisateur
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_video_show', ['id' => $video->getId()]);
    }
}
