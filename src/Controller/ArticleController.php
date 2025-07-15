<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ArticleCategorie;
use App\Entity\Stock;
use App\Repository\ArticleCategorieRepository;
use App\Repository\ArticleRepository;
use App\Repository\StockRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ArticleController extends AbstractController
{
    #[Route('/article', name: 'app_article')]
    public function index(ArticleRepository $articleRepository, ArticleCategorieRepository $categoryRepository): Response
    {
        $articles = $articleRepository->findAll();
        $categories = $categoryRepository->findAll();
        
        return $this->render('article/index.html.twig', [
            'activeLink' => 'article',
            'articles' => $articles,
            'categories' => $categories,
        ]);
    }

    // helper function to render the article creation form
    #[Route('/article/new', name: 'app_article_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ArticleRepository $articleRepository): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $data = json_decode($request->getContent(), true);
                
                $errors = [];
                if (empty($data['nom'])) $errors[] = 'Le nom est requis';
                if (empty($data['reference'])) $errors[] = 'La référence est requise';
                if (empty($data['marque'])) $errors[] = 'La marque est requise';
                if (empty($data['unite'])) $errors[] = 'L\'unité est requise';
                if (!isset($data['prix']) || !is_numeric($data['prix'])) $errors[] = 'Le prix doit être un nombre';
                if (empty($data['type'])) $errors[] = 'Le type est requis';
                if (isset($data['numero']) && $data['numero'] !== '' && !is_numeric($data['numero'])) $errors[] = 'Le numéro doit être un nombre';

                if (!empty($errors)) {
                    return $this->json(['success' => false, 'errors' => $errors], 400);
                }

                $existingArticle = $articleRepository->findOneBy(['reference' => $data['reference']]);
                if ($existingArticle) {
                    return $this->json(['success' => false, 'errors' => ['Cette référence existe déjà']], 400);
                }

                $article = new Article();
                $article->setNom($data['nom'])
                       ->setReference($data['reference'])
                       ->setMarque($data['marque'])
                       ->setUnite($data['unite'])
                       ->setPrix((float)$data['prix'])
                       ->setDescription($data['description'] ?? '')
                       ->setType($data['type']);
                
                if (isset($data['numero']) && $data['numero'] !== '') {
                    $article->setNumero((int)$data['numero']);
                } else {
                    $article->setNumero(null);
                }

                $articleRepository->save($article, true);

                return $this->json([
                    'success' => true, 
                    'message' => 'Article créé avec succès',
                    'article' => [
                        'id' => $article->getId(),
                        'nom' => $article->getNom(),
                        'reference' => $article->getReference(),
                        'marque' => $article->getMarque(),
                        'unite' => $article->getUnite(),
                        'prix' => $article->getPrix(),
                        'description' => $article->getDescription(),
                        'type' => $article->getType(),
                        'numero' => $article->getNumero()
                    ]
                ]);

            } catch (\Exception $e) {
                return $this->json(['success' => false, 'errors' => ['Erreur lors de la création: ' . $e->getMessage()]], 500);
            }
        }

        return $this->render('article/new.html.twig', [
            'activeLink' => 'article',
        ]);
    }

    #[Route('/article/create', name: 'app_article_create', methods: ['POST'])]
    public function create(Request $request, ArticleRepository $articleRepository, ArticleCategorieRepository $categoryRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $errors = [];
            if (empty($data['nom'])) $errors[] = 'Le nom est requis';
            if (empty($data['reference'])) $errors[] = 'La référence est requise';
            if (empty($data['marque'])) $errors[] = 'La marque est requise';
            if (empty($data['unite'])) $errors[] = 'L\'unité est requise';
            if (!isset($data['prix']) || !is_numeric($data['prix'])) $errors[] = 'Le prix doit être un nombre';
            if (empty($data['type'])) $errors[] = 'Le type est requis';
            if (isset($data['numero']) && $data['numero'] !== '' && !is_numeric($data['numero'])) $errors[] = 'Le numéro doit être un nombre';
            if (empty($data['category'])) $errors[] = 'La catégorie est requise';

            if (!empty($errors)) {
                return $this->json(['success' => false, 'errors' => $errors], 400);
            }

            $existingArticle = $articleRepository->findOneBy(['reference' => $data['reference']]);
            if ($existingArticle) {
                return $this->json(['success' => false, 'errors' => ['Cette référence existe déjà']], 400);
            }

            $category = null;
            if (!empty($data['category'])) {
                $category = $categoryRepository->find($data['category']);
                if (!$category) {
                    return $this->json(['success' => false, 'errors' => ['Catégorie non trouvée']], 400);
                }
            }

            $article = new Article();
            $article->setNom($data['nom'])
                   ->setReference($data['reference'])
                   ->setMarque($data['marque'])
                   ->setUnite($data['unite'])
                   ->setPrix((float)$data['prix'])
                   ->setDescription($data['description'] ?? '')
                   ->setType($data['type'])
                   ->setCategory($category);
            
            if (isset($data['numero']) && $data['numero'] !== '') {
                $article->setNumero((int)$data['numero']);
            } else {
                $article->setNumero(null);
            }

            $articleRepository->save($article, true);

            return $this->json([
                'success' => true, 
                'message' => 'Article créé avec succès',
                'article' => [
                    'id' => $article->getId(),
                    'nom' => $article->getNom(),
                    'reference' => $article->getReference(),
                    'marque' => $article->getMarque(),
                    'unite' => $article->getUnite(),
                    'prix' => $article->getPrix(),
                    'description' => $article->getDescription(),
                    'type' => $article->getType(),
                    'numero' => $article->getNumero(),
                    'category' => $article->getCategory() ? [
                        'id' => $article->getCategory()->getId(),
                        'nom' => $article->getCategory()->getNom()
                    ] : null
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'errors' => ['Erreur lors de la création: ' . $e->getMessage()]], 500);
        }
    }

    #[Route('/article/{id}', name: 'app_article_get', methods: ['GET'])]
    public function getArticle(Article $article): Response
    {
        return $this->json([
            'id' => $article->getId(),
            'nom' => $article->getNom(),
            'reference' => $article->getReference(),
            'marque' => $article->getMarque(),
            'unite' => $article->getUnite(),
            'prix' => $article->getPrix(),
            'description' => $article->getDescription(),
            'type' => $article->getType(),
            'numero' => $article->getNumero(),
            'category' => $article->getCategory() ? $article->getCategory()->getId() : null
        ]);
    }
    
    #[Route('/article/{id}/update', name: 'app_article_update', methods: ['PUT'])]
    public function update(Request $request, Article $article, ArticleRepository $articleRepository, ArticleCategorieRepository $categoryRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $errors = [];
            if (empty($data['nom'])) $errors[] = 'Le nom est requis';
            if (empty($data['reference'])) $errors[] = 'La référence est requise';
            if (empty($data['marque'])) $errors[] = 'La marque est requise';
            if (empty($data['unite'])) $errors[] = 'L\'unité est requise';
            if (!isset($data['prix']) || !is_numeric($data['prix'])) $errors[] = 'Le prix doit être un nombre';
            if (empty($data['type'])) $errors[] = 'Le type est requis';
            if (isset($data['numero']) && $data['numero'] !== '' && !is_numeric($data['numero'])) $errors[] = 'Le numéro doit être un nombre';
            if (empty($data['category'])) $errors[] = 'La catégorie est requise';

            if (!empty($errors)) {
                return $this->json(['success' => false, 'errors' => $errors], 400);
            }

            $existingArticle = $articleRepository->findOneBy(['reference' => $data['reference']]);
            if ($existingArticle && $existingArticle->getId() !== $article->getId()) {
                return $this->json(['success' => false, 'errors' => ['Cette référence existe déjà']], 400);
            }
            
            $category = null;
            if (!empty($data['category'])) {
                $category = $categoryRepository->find($data['category']);
                if (!$category) {
                    return $this->json(['success' => false, 'errors' => ['Catégorie non trouvée']], 400);
                }
            }

            $article->setNom($data['nom'])
                   ->setReference($data['reference'])
                   ->setMarque($data['marque'])
                   ->setUnite($data['unite'])
                   ->setPrix((float)$data['prix'])
                   ->setDescription($data['description'] ?? '')
                   ->setType($data['type'])
                   ->setCategory($category);
                   
            if (isset($data['numero']) && $data['numero'] !== '') {
                $article->setNumero((int)$data['numero']);
            } else {
                $article->setNumero(null);
            }

            $articleRepository->save($article, true);

            return $this->json([
                'success' => true, 
                'message' => 'Article mis à jour avec succès',
                'article' => [
                    'id' => $article->getId(),
                    'nom' => $article->getNom(),
                    'reference' => $article->getReference(),
                    'marque' => $article->getMarque(),
                    'unite' => $article->getUnite(),
                    'prix' => $article->getPrix(),
                    'description' => $article->getDescription(),
                    'type' => $article->getType(),
                    'numero' => $article->getNumero(),
                    'category' => $article->getCategory() ? [
                        'id' => $article->getCategory()->getId(),
                        'nom' => $article->getCategory()->getNom()
                    ] : null
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'errors' => ['Erreur lors de la mise à jour: ' . $e->getMessage()]], 500);
        }
    }
    
    #[Route('/article/{id}/show', name: 'app_article_show', methods: ['GET'])]
    public function show(int $id, ArticleRepository $articleRepository, ArticleCategorieRepository $categoryRepository): Response
    {
        $article = $articleRepository->find($id);
        
        if (!$article) {
            $this->addFlash('error', 'Article non trouvé');
            return $this->redirectToRoute('app_article');
        }
        
        return $this->render('article/show.html.twig', [
            'activeLink' => 'article',
            'article' => $article,
            'categories' => $categoryRepository->findAll()
        ]);
    }

    #[Route('/article/{articleId}/stock/{chantierId}', name: 'app_article_stock_details', methods: ['GET'])]
    public function getStockDetails(int $articleId, int $chantierId, StockRepository $stockRepository): Response
    {
        try {
            $stock = $stockRepository->findOneBy(['article' => $articleId, 'chantier' => $chantierId]);

            if (!$stock) {
                return $this->json(['success' => false, 'errors' => ['Stock non trouvé pour cet article et ce chantier']], 404);
            }

            return $this->json([
                'success' => true,
                'stock' => [
                    'lieu' => $stock->getChantier() ? $stock->getChantier()->getNom() : 'N/A',
                    'quantite_chantier' => $stock->getQuantiteChantier() ?? 0,
                    'bon_etat' => $stock->getBonEtat() ?? 0,
                    'mauvais_etat' => $stock->getMauvaisEtat() ?? 0,
                    'feraille_etat' => $stock->getFerailleEtat() ?? 0,
                    'unite' => $stock->getArticle() ? $stock->getArticle()->getUnite() : 'N/A'
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'errors' => ['Erreur lors de la récupération des détails du stock: ' . $e->getMessage()]], 500);
        }
    }
}