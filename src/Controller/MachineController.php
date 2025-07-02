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

    #[Route('/machine/new', name: 'app_machine_new', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('machine/new.html.twig');
    }

    #[Route('/machine/{id}/edit', name: 'app_machine_edit', methods: ['GET'])]
    public function edit(int $id): Response
    {
        return $this->render('machine/edit.html.twig', [
            'machine_id' => $id,
        ]);
    }
}
