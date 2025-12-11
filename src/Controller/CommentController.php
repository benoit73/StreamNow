<?php

namespace App\Controller;

use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/comment')]
final class CommentController extends AbstractController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/{id}/like', name: 'app_comment_like', methods: ['POST'])]
    public function like(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('like' . $comment->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_video_show', ['id' => $comment->getVideo()->getId()]);
        }

        // Incrémenter les likes
        $comment->setLikes($comment->getLikes() + 1);
        $entityManager->flush();

        return $this->redirectToRoute('app_video_show', [
            'id' => $comment->getVideo()->getId(),
            '_fragment' => 'comment-' . $comment->getId()
        ]);
    }
}
