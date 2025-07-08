<?php

namespace App\Controller;

use App\Entity\DemandeAchat;
use App\Entity\DemandeDetails;
use App\Entity\Article;
use App\Repository\DemandeAchatRepository;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DemandAchatController extends AbstractController
{
    #[Route('/demande/achat', name: 'app_demand_achat')]
    public function index(DemandeAchatRepository $demandeAchatRepository): Response
    {   
        $demandes = $demandeAchatRepository->findAll();
        
        return $this->render('demand_achat/index.html.twig', [
            'demandes' => $demandes,
            'activeLink' => 'demande_achat',
        ]);
    }

    #[Route('/demande/achat/nouvelle', name: 'app_demand_achat_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, ArticleRepository $articleRepository): Response
    {
        // Cette action sera pour le formulaire de création
        $articles = $articleRepository->findAll();
        
        return $this->render('demand_achat/new.html.twig', [
            'articles' => $articles,
            'activeLink' => 'demande_achat',
        ]);
    }

    #[Route('/demande/achat/{id}', name: 'app_demand_achat_show', methods: ['GET'])]
    public function show(DemandeAchat $demandeAchat): Response
    {
        return $this->render('demand_achat/show.html.twig', [
            'demande' => $demandeAchat,
            'activeLink' => 'demande_achat',
        ]);
    }
}
