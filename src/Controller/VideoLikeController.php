<?php

namespace App\Controller;

use App\Entity\Video;
use App\Entity\VideoLike;
use App\Repository\VideoLikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/video-like')]
final class VideoLikeController extends AbstractController
{
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
