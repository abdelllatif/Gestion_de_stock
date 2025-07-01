<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ChantierController extends AbstractController
{
    #[Route('/chantier', name: 'app_chantier')]
    public function index(): Response
    {
        return $this->render('chantier/index.html.twig', [
            'controller_name' => 'ChantierController',
        ]);
    }
}
