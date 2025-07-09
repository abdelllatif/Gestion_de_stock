<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ChantierRepository;

final class DemandAchatController extends AbstractController
{
    #[Route('/demande/achat', name: 'app_demand_achat')]
    public function index(ChantierRepository $chantierRepository): Response
    {   
        $activeLink = 'demande_achat';
        $chantiers = $chantierRepository->findAll();
        return $this->render('demand_achat/index.html.twig', [
            'controller_name' => 'DemandAchatController',
            'activeLink' => 'demand_achat',
            'chantiers' => $chantiers,
        ]);
    }
}
