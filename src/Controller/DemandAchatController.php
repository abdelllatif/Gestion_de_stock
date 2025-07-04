<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DemandAchatController extends AbstractController
{
    #[Route('/demand/achat', name: 'app_demand_achat')]
    public function index(): Response
    {
        return $this->render('demand_achat/index.html.twig', [
            'controller_name' => 'DemandAchatController',
        ]);
    }
}
