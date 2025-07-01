<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommandeClientController extends AbstractController
{
    #[Route('/commande/client', name: 'app_commande_client')]
    public function index(): Response
    {
        return $this->render('commande_client/index.html.twig', [
            'controller_name' => 'CommandeClientController',
        ]);
    }
}
