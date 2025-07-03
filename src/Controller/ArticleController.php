<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ArticleController extends AbstractController
{
    #[Route('/article', name: 'app_article')]
    public function index(): Response
    {
        return $this->render('article/index.html.twig', [
            'activeLink' => 'article',
        ]);
    }

    #[Route('/article/new', name: 'app_article_new', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('article/new.html.twig', [
            'activeLink' => 'article',
        ]);
    }

    #[Route('/article/{id}/edit', name: 'app_article_edit', methods: ['GET'])]
    public function edit(int $id): Response
    {
        return $this->render('article/edit.html.twig', [
            'article_id' => $id,
            'activeLink' => 'article',
        ]);
    }
}
