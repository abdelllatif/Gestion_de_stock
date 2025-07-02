<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UtilisateurController extends AbstractController
{
    #[Route('/utilisateur', name: 'app_utilisateur')]
    public function index(): Response
    {
        return $this->render('utilisateur/index.html.twig', [
            'controller_name' => 'UtilisateurController',
        ]);
    }

    #[Route('/utilisateur/new', name: 'app_utilisateur_new', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('utilisateur/new.html.twig');
    }

    #[Route('/utilisateur/{id}/edit', name: 'app_utilisateur_edit', methods: ['GET'])]
    public function edit(int $id): Response
    {
        // TODO: Fetch the user by $id and pass to the template if needed
        return $this->render('utilisateur/edit.html.twig', [
            'user_id' => $id,
        ]);
    }
}
