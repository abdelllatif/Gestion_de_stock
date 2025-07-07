<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BonController extends AbstractController
{
    #[Route('/bon', name: 'app_bon')]
    public function index(): Response
    {
        return $this->render('bon/index.html.twig', [
            'controller_name' => 'BonController',
            'activeLink' => 'bon',
        ]);
    }
}
