<?php

namespace App\Controller;

use App\Entity\Bon;
use App\Entity\BonDetails;
use App\Entity\Article;
use App\Repository\BonRepository;
use App\Repository\ArticleRepository;
use App\Repository\ChantierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class BonController extends AbstractController
{
    #[Route('/bon', name: 'app_bon')]
    public function index(BonRepository $bonRepository): Response
    {
        $bons = $bonRepository->findBy([], ['date' => 'DESC']);
        
        return $this->render('bon/index.html.twig', [
            'bons' => $bons,
            'activeLink' => 'bon',
        ]);
    }
    
    #[Route('/bon/nouveau', name: 'app_bon_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ArticleRepository $articleRepository, ChantierRepository $chantierRepository): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $data = json_decode($request->getContent(), true);
                
                // Validation de base
                if (empty($data['type']) || empty($data['numero_serie'])) {
                    return $this->json(['success' => false, 'message' => 'Données incomplètes'], 400);
                }
                
                // Vérifier si le numéro de série est déjà utilisé
                $existingBon = $entityManager->getRepository(Bon::class)->findOneBy(['numero_serie' => $data['numero_serie']]);
                if ($existingBon) {
                    return $this->json(['success' => false, 'message' => 'Ce numéro de série est déjà utilisé'], 400);
                }
                
                // Créer le bon
                $bon = new Bon();
                $bon->setType($data['type']);
                $bon->setNumeroSerie($data['numero_serie']);
                $bon->setNote($data['note'] ?? '');
                $bon->setDate(new \DateTime($data['date'] ?? 'now'));
                
                // Associer le chantier si fourni
                if (!empty($data['chantier_id'])) {
                    $chantier = $chantierRepository->find($data['chantier_id']);
                    if ($chantier) {
                        $bon->setChantier($chantier);
                    }
                }
                
                // Traiter les articles du bon
                if (!empty($data['articles'])) {
                    foreach ($data['articles'] as $articleData) {
                        // Vérifier si l'article existe
                        $article = $articleRepository->find($articleData['id']);
                        if (!$article) {
                            continue;
                        }
                        
                        $bonDetail = new BonDetails();
                        $bonDetail->setQuantite($articleData['quantite']);
                        $bonDetail->setFournisseur($articleData['fournisseur'] ?? '');
                        $bonDetail->setUnite($article->getUnite() ?? 'Unité');
                        $bonDetail->setBon($bon);
                        
                        // Gestion du stock selon le type de bon
                        $this->updateStockForBonDetail($entityManager, $bon, $article, $articleData['quantite']);
                        
                        $entityManager->persist($bonDetail);
                    }
                }
                
                $entityManager->persist($bon);
                $entityManager->flush();
                
                return $this->json([
                    'success' => true, 
                    'message' => 'Bon créé avec succès',
                    'id' => $bon->getId()
                ]);
                
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false, 
                    'message' => 'Erreur lors de la création: ' . $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }
        }
        
        // Si requête GET, afficher le formulaire
        $articles = $articleRepository->findAll();
        $chantiers = $chantierRepository->findAll();
        
        return $this->render('bon/new.html.twig', [
            'articles' => $articles,
            'chantiers' => $chantiers,
            'activeLink' => 'bon',
        ]);
    }
    
    #[Route('/bon/{id}', name: 'app_bon_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Bon $bon): Response
    {
        return $this->render('bon/show.html.twig', [
            'bon' => $bon,
            'activeLink' => 'bon',
        ]);
    }
    
    #[Route('/bon/{id}/edit', name: 'app_bon_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Bon $bon, EntityManagerInterface $entityManager, ArticleRepository $articleRepository, ChantierRepository $chantierRepository): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $data = json_decode($request->getContent(), true);
                
                // Validation de base
                if (empty($data['type']) || empty($data['numero_serie'])) {
                    return $this->json(['success' => false, 'message' => 'Données incomplètes'], 400);
                }
                
                // Vérifier si le numéro de série est déjà utilisé par un autre bon
                $existingBon = $entityManager->getRepository(Bon::class)->findOneBy(['numero_serie' => $data['numero_serie']]);
                if ($existingBon && $existingBon->getId() !== $bon->getId()) {
                    return $this->json(['success' => false, 'message' => 'Ce numéro de série est déjà utilisé'], 400);
                }
                
                // Mettre à jour les informations de base
                $bon->setType($data['type']);
                $bon->setNumeroSerie($data['numero_serie']);
                $bon->setNote($data['note'] ?? $bon->getNote());
                
                if (!empty($data['date'])) {
                    $bon->setDate(new \DateTime($data['date']));
                }
                
                // Associer le chantier si fourni
                if (isset($data['chantier_id'])) {
                    if (!empty($data['chantier_id'])) {
                        $chantier = $chantierRepository->find($data['chantier_id']);
                        if ($chantier) {
                            $bon->setChantier($chantier);
                        }
                    } else {
                        $bon->setChantier(null);
                    }
                }
                
                // Si des modifications d'articles sont demandées, les traiter
                if (!empty($data['modified_details'])) {
                    // TODO: Implémentation de la modification des détails du bon
                }
                
                $entityManager->flush();
                
                return $this->json([
                    'success' => true, 
                    'message' => 'Bon mis à jour avec succès',
                    'id' => $bon->getId()
                ]);
                
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false, 
                    'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
                ], 500);
            }
        }
        
        $articles = $articleRepository->findAll();
        $chantiers = $chantierRepository->findAll();
        
        return $this->render('bon/edit.html.twig', [
            'bon' => $bon,
            'articles' => $articles,
            'chantiers' => $chantiers,
            'activeLink' => 'bon',
        ]);
    }
    
    #[Route('/bon/{id}/delete', name: 'app_bon_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Bon $bon, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Pour chaque détail du bon, inverser l'effet sur le stock
            foreach ($bon->getBonDetails() as $detail) {
                // Logique de mise à jour du stock à implémenter
            }
            
            $entityManager->remove($bon);
            $entityManager->flush();
            
            return $this->json(['success' => true, 'message' => 'Bon supprimé avec succès']);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false, 
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Met à jour le stock en fonction du type de bon et de l'article
     */
    private function updateStockForBonDetail(EntityManagerInterface $entityManager, Bon $bon, Article $article, int $quantite): void
    {
        // TODO: Implémenter la logique de mise à jour du stock selon le type de bon
        // Par exemple:
        // - Si c'est un bon de sortie, diminuer le stock
        // - Si c'est un bon d'entrée, augmenter le stock
        // - Si c'est un transfert, ajuster les stocks entre chantiers
    }
}
