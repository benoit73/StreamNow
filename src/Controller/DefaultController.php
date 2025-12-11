<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\VideoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(VideoRepository $videoRepository, CategoryRepository $categoryRepository): Response
    {
        $videos = $videoRepository->findBy([], ['createdAt' => 'DESC']);
        $categories = $categoryRepository->findAll();

        return $this->render('default/index.html.twig', [
            'videos' => $videos,
            'categories' => $categories,
        ]);
    }

    #[Route('/recherche', name: 'app_search')]
    public function search(Request $request, VideoRepository $videoRepository): Response
    {
        $query = trim($request->query->get('q', ''));
        $videos = [];

        if ($query !== '') {
            $videos = $videoRepository->search($query);
        }

        return $this->render('default/search.html.twig', [
            'videos' => $videos,
            'query' => $query,
        ]);
    }

    #[Route('/abonnements', name: 'app_subscriptions')]
    public function subscriptions(VideoRepository $videoRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer les abonnements de l'utilisateur
        $subscriptions = $user->getAbonnements();
        
        // Récupérer les vidéos des créateurs auxquels l'utilisateur est abonné
        $videos = [];
        if ($subscriptions->count() > 0) {
            $videos = $videoRepository->findByOwners($subscriptions->toArray());
        }

        return $this->render('default/subscriptions.html.twig', [
            'subscriptions' => $subscriptions,
            'videos' => $videos,
        ]);
    }

    #[Route('/categorie/{id}', name: 'app_category')]
    public function category(Category $category, VideoRepository $videoRepository): Response
    {
        $videos = $videoRepository->findBy(
            ['category' => $category],
            ['createdAt' => 'DESC']
        );

        return $this->render('default/category.html.twig', [
            'category' => $category,
            'videos' => $videos,
        ]);
    }
}
