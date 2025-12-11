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
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('', name: 'app_user')]
    public function index(): Response
    {
        
        return $this->render('user/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/{id}', name: 'app_user_profile', requirements: ['id' => '\d+'])]
    public function profile(User $user): Response
    {
        // Vérifier si l'utilisateur connecté est abonné
        $isSubscribed = false;
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        if ($currentUser) {
            $isSubscribed = $currentUser->getAbonnements()->contains($user);
        }

        return $this->render('user/profile.html.twig', [
            'user' => $user,
            'isSubscribed' => $isSubscribed,
        ]);
    }

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

        // Rediriger vers la page d'où vient l'utilisateur
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_user_profile', ['id' => $user->getId()]);
    }
}

