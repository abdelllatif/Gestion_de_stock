<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DepotController extends AbstractController
{
    #[Route('/depot', name: 'app_depot')]
    public function index(): Response
    {
        return $this->render('depot/index.html.twig', [
            'controller_name' => 'DepotController',
            'activeLink' => 'depot',
        ]);
    }
}
