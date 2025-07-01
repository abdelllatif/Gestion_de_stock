<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MachineController extends AbstractController
{
    #[Route('/machine', name: 'app_machine')]
    public function index(): Response
    {
        return $this->render('machine/index.html.twig', [
            'controller_name' => 'MachineController',
        ]);
    }
}
