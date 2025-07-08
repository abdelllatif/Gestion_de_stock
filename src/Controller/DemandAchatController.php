<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DemandAchatController extends AbstractController
{
    #[Route('/demande/achat', name: 'app_demand_achat')]
    public function index(): Response
    {   
        $activeLink = 'demande_achat';
        return $this->render('demand_achat/index.html.twig', [
            'controller_name' => 'DemandAchatController',
<<<<<<< HEAD
            'activeLink' => $activeLink
=======
            'activeLink' => 'demand_achat',
>>>>>>> origin/Machines
        ]);
    }
}
