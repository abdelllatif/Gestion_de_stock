<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DasheboreedController extends AbstractController
{
    #[Route('/', name: 'dashboard', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('dasheboreed/index.html.twig', [
            'activeLink' => 'dashboard',
        ]);
    }
}
