<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\CommentLike;
use App\Repository\CommentLikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/comment-like')]
final class CommentLikeController extends AbstractController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/{id}/like', name: 'app_comment_like', methods: ['POST'])]
    public function like(Request $request, Comment $comment, EntityManagerInterface $entityManager, CommentLikeRepository $commentLikeRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('like'.$comment->getId(), $request->request->get('_token'))) {
            // Chercher si l'utilisateur a déjà voté
            $existingVote = $commentLikeRepository->findOneBy(['comment' => $comment, 'owner' => $user]);

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
                $vote = new CommentLike();
                $vote->setComment($comment);
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

        return $this->redirectToRoute('app_video_show', ['id' => $comment->getVideo()->getId()]);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/{id}/dislike', name: 'app_comment_dislike', methods: ['POST'])]
    public function dislike(Request $request, Comment $comment, EntityManagerInterface $entityManager, CommentLikeRepository $commentLikeRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('dislike'.$comment->getId(), $request->request->get('_token'))) {
            // Chercher si l'utilisateur a déjà voté
            $existingVote = $commentLikeRepository->findOneBy(['comment' => $comment, 'owner' => $user]);

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
                $vote = new CommentLike();
                $vote->setComment($comment);
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

        return $this->redirectToRoute('app_video_show', ['id' => $comment->getVideo()->getId()]);
    }
}
