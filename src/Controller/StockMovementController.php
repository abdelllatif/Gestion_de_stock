<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StockMovementController extends AbstractController
{
    #[Route('/stock/movement', name: 'app_stock_movement')]
    public function index(): Response
    {
        return $this->render('stock_movement/index.html.twig', [
            'controller_name' => 'StockMovementController',
        ]);
    }

    #[Route('/stock_movement', name: 'app_stock_movement_list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('stock_movement/index.html.twig');
    }

    #[Route('/stock_movement/new', name: 'app_stock_movement_new', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('stock_movement/new.html.twig');
    }

    #[Route('/stock_movement/{id}/edit', name: 'app_stock_movement_edit', methods: ['GET'])]
    public function edit(int $id): Response
    {
        return $this->render('stock_movement/edit.html.twig', [
            'movement_id' => $id,
        ]);
    }
}
