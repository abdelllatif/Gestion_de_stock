<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ChauffeurController extends AbstractController
{
    #[Route('/chauffeur', name: 'app_chauffeur')]
    public function index(): Response
    {
        return $this->render('chauffeur/index.html.twig', [
            'controller_name' => 'ChauffeurController',
        ]);
    }
}
