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
            'activeLink' => 'chauffeur',
        ]);
    }

    #[Route('/chauffeur/new', name: 'app_chauffeur_new', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('chauffeur/new.html.twig', [
            'activeLink' => 'chauffeur',
        ]);
    }

    #[Route('/chauffeur/{id}/edit', name: 'app_chauffeur_edit', methods: ['GET'])]
    public function edit(int $id): Response
    {
        return $this->render('chauffeur/edit.html.twig', [
            'chauffeur_id' => $id,
            'activeLink' => 'chauffeur',
        ]);
    }
}
