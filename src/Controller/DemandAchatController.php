<?php

namespace App\Controller;

use App\Entity\DemandeAchat;
use App\Entity\DemandeDetails;
use App\Entity\Article;
use App\Repository\DemandeAchatRepository;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ChantierRepository;

final class DemandAchatController extends AbstractController
{
    #[Route('/demande/achat', name: 'app_demand_achat')]
    public function index(DemandeAchatRepository $demandeAchatRepository): Response
    {   
        $demandes = $demandeAchatRepository->findAll();
        
        return $this->render('demand_achat/index.html.twig', [
            'demandes' => $demandes,
            'activeLink' => 'demande_achat',
        ]);
    }

    #[Route('/demande/achat/nouvelle', name: 'app_demand_achat_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, ArticleRepository $articleRepository): Response
    {
        // Si c'est une requête POST, on traite la soumission du formulaire
        if ($request->isMethod('POST')) {
            try {
                $data = json_decode($request->getContent(), true);
                
                if (empty($data['date']) || empty($data['etat']) || !isset($data['tva'])) {
                    return $this->json(['success' => false, 'message' => 'Données incomplètes'], 400);
                }
                
                if ((empty($data['existingArticles']) || count($data['existingArticles']) === 0) && 
                    (empty($data['newArticles']) || count($data['newArticles']) === 0)) {
                    return $this->json(['success' => false, 'message' => 'Aucun article dans la demande'], 400);
                }
                
                // Créer la demande d'achat
                $demandeAchat = new DemandeAchat();
                $demandeAchat->setDate(new \DateTime($data['date']));
                $demandeAchat->setEtat($data['etat']);
                $demandeAchat->setTva($data['tva']);
                
                // On pourrait aussi associer l'utilisateur connecté
                // $demandeAchat->setUilisateur($this->getUser());
                
                // Initialiser les montants
                $montantHT = 0;
                
                // Traiter les nouveaux articles
                if (!empty($data['newArticles'])) {
                    foreach ($data['newArticles'] as $newArticleData) {
                        // Créer le nouvel article
                        $article = new Article();
                        $article->setReference($newArticleData['reference']);
                        $article->setNom($newArticleData['nom']);
                        $article->setMarque($newArticleData['marque'] ?? '');
                        $article->setUnite($newArticleData['unite'] ?? 'Unité');
                        $article->setType($newArticleData['type'] ?? 'Consommable');
                        $article->setDescription($newArticleData['description'] ?? '');
                        $article->setPrix($newArticleData['prix']);
                        
                        // Persister l'article
                        $entityManager->persist($article);
                        
                        // Créer les détails de demande
                        $demandeDetail = new DemandeDetails();
                        $demandeDetail->setArticle($article);
                        $demandeDetail->setQuantite($newArticleData['quantite']);
                        $demandeDetail->setPrixUnitaire($newArticleData['prix']);
                        $demandeDetail->setFournisseur($newArticleData['fournisseur'] ?? '');
                        
                        $prixTotal = $newArticleData['prix'] * $newArticleData['quantite'];
                        $demandeDetail->setPrixTotal($prixTotal);
                        
                        // Ajouter à la demande
                        $demandeDetail->setDemandeAchat($demandeAchat);
                        $entityManager->persist($demandeDetail);
                        
                        // Mettre à jour le montant HT
                        $montantHT += $prixTotal;
                    }
                }
                
                // Traiter les articles existants
                if (!empty($data['existingArticles'])) {
                    foreach ($data['existingArticles'] as $existingArticleData) {
                        $article = $articleRepository->find($existingArticleData['articleId']);
                        
                        if (!$article) {
                            continue;
                        }
                        
                        // Créer les détails de demande
                        $demandeDetail = new DemandeDetails();
                        $demandeDetail->setArticle($article);
                        $demandeDetail->setQuantite($existingArticleData['quantite']);
                        $demandeDetail->setPrixUnitaire($existingArticleData['prixUnitaire']);
                        $demandeDetail->setFournisseur($existingArticleData['fournisseur'] ?? '');
                        
                        $prixTotal = $existingArticleData['prixUnitaire'] * $existingArticleData['quantite'];
                        $demandeDetail->setPrixTotal($prixTotal);
                        
                        // Ajouter à la demande
                        $demandeDetail->setDemandeAchat($demandeAchat);
                        $entityManager->persist($demandeDetail);
                        
                        // Mettre à jour le montant HT
                        $montantHT += $prixTotal;
                    }
                }
                
                // Calculer les montants
                $demandeAchat->setMontantHT($montantHT);
                $montantTTC = $montantHT * (1 + ($data['tva'] / 100));
                $demandeAchat->setMontantTTC($montantTTC);
                
                // Persister la demande
                $entityManager->persist($demandeAchat);
                $entityManager->flush();
                
                return $this->json([
                    'success' => true, 
                    'message' => 'Demande créée avec succès',
                    'id' => $demandeAchat->getId()
                ]);
                
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false, 
                    'message' => 'Erreur lors de la création: ' . $e->getMessage()
                ], 500);
            }
        }
        
        // Si c'est une requête GET, on affiche le formulaire
        $articles = $articleRepository->findAll();
        
        return $this->render('demand_achat/new.html.twig', [
            'articles' => $articles,
            'activeLink' => 'demande_achat',
        ]);
    }

    #[Route('/demande/achat/{id}', name: 'app_demand_achat_show', methods: ['GET'])]
    public function show(DemandeAchat $demandeAchat): Response
    {
        return $this->render('demand_achat/show.html.twig', [
            'demande' => $demandeAchat,
            'activeLink' => 'demande_achat',
        ]);
    }

    #[Route('/demande/achat/{id}/data', name: 'app_demand_achat_data', methods: ['GET'])]
    public function getData(DemandeAchat $demandeAchat): Response
    {
        // Retourner les données de la demande au format JSON
        $data = [
            'id' => $demandeAchat->getId(),
            'date' => $demandeAchat->getDate()->format('Y-m-d'),
            'etat' => $demandeAchat->getEtat(),
            'tva' => $demandeAchat->getTva(),
            'montantHT' => $demandeAchat->getMontantHT(),
            'montantTTC' => $demandeAchat->getMontantTTC(),
        ];

        return $this->json($data);
    }
    
    #[Route('/demande/achat/{id}/edit', name: 'app_demand_achat_edit', methods: ['POST'])]
    public function edit(Request $request, DemandeAchat $demandeAchat, EntityManagerInterface $entityManager): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (empty($data['date']) || empty($data['etat']) || !isset($data['tva'])) {
                return $this->json(['success' => false, 'message' => 'Données incomplètes'], 400);
            }
            
            // Mettre à jour les données de base
            $demandeAchat->setDate(new \DateTime($data['date']));
            $demandeAchat->setEtat($data['etat']);
            
            // Ne recalculer les montants que si la TVA change
            if ($demandeAchat->getTva() != $data['tva']) {
                $demandeAchat->setTva($data['tva']);
                
                // Récupérer le montant HT existant
                $montantHT = $demandeAchat->getMontantHT();
                
                // Recalculer le montant TTC avec la nouvelle TVA
                $montantTTC = $montantHT * (1 + ($data['tva'] / 100));
                $demandeAchat->setMontantTTC($montantTTC);
            }
            
            $entityManager->flush();
            
            return $this->json([
                'success' => true, 
                'message' => 'Demande mise à jour avec succès',
                'id' => $demandeAchat->getId()
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false, 
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }
    
    #[Route('/demande/achat/{id}/status', name: 'app_demand_achat_update_status', methods: ['POST'])]
    public function updateStatus(Request $request, DemandeAchat $demandeAchat, EntityManagerInterface $entityManager): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (empty($data['etat'])) {
                return $this->json(['success' => false, 'message' => 'Statut non spécifié'], 400);
            }
            
            // Mettre à jour le statut
            $demandeAchat->setEtat($data['etat']);
            $entityManager->flush();
            
            return $this->json([
                'success' => true, 
                'message' => 'Statut mis à jour avec succès',
                'id' => $demandeAchat->getId(),
                'etat' => $demandeAchat->getEtat()
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false, 
                'message' => 'Erreur lors de la mise à jour du statut: ' . $e->getMessage()
            ], 500);
        }
    }
    
    #[Route('/demande/achat/{id}/edit-form', name: 'app_demand_achat_edit_form', methods: ['GET'])]
    public function editForm(DemandeAchat $demandeAchat, ArticleRepository $articleRepository): Response
    {
        // Vérifier si la demande est modifiable (en attente)
        if ($demandeAchat->getEtat() !== 'En attente') {
            $this->addFlash('error', 'Seules les demandes en attente peuvent être modifiées.');
            return $this->redirectToRoute('app_demand_achat_show', ['id' => $demandeAchat->getId()]);
        }
        
        // Récupérer tous les articles pour le sélecteur
        $articles = $articleRepository->findAll();
        
        return $this->render('demand_achat/edit.html.twig', [
            'demande' => $demandeAchat,
            'articles' => $articles,
            'activeLink' => 'demande_achat',
        ]);
    }

    #[Route('/demande/achat/{id}/edit', name: 'app_demand_achat_edit_page', methods: ['GET', 'POST'])]
    public function editPage(Request $request, DemandeAchat $demandeAchat, ArticleRepository $articleRepository, EntityManagerInterface $entityManager): Response
    {
        // Autoriser l'édition uniquement si la demande est en attente
        if ($demandeAchat->getEtat() !== 'En attente') {
            $this->addFlash('danger', "Seules les demandes en attente peuvent être modifiées.");
            return $this->redirectToRoute('app_demand_achat');
        }

        // Si POST, traiter la soumission (similaire à new)
        if ($request->isMethod('POST')) {
            try {
                $data = json_decode($request->getContent(), true);
                
                if (empty($data['date']) || empty($data['etat']) || !isset($data['tva'])) {
                    return $this->json(['success' => false, 'message' => 'Données incomplètes'], 400);
                }
                
                // Mettre à jour les données de base
                $demandeAchat->setDate(new \DateTime($data['date']));
                $demandeAchat->setEtat($data['etat']);
                
                // Ne recalculer les montants que si la TVA change
                if ($demandeAchat->getTva() != $data['tva']) {
                    $demandeAchat->setTva($data['tva']);
                    
                    // Récupérer le montant HT existant
                    $montantHT = $demandeAchat->getMontantHT();
                    
                    // Recalculer le montant TTC avec la nouvelle TVA
                    $montantTTC = $montantHT * (1 + ($data['tva'] / 100));
                    $demandeAchat->setMontantTTC($montantTTC);
                }
                
                $entityManager->flush();
                
                return $this->json([
                    'success' => true, 
                    'message' => 'Demande mise à jour avec succès',
                    'id' => $demandeAchat->getId()
                ]);
                
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false, 
                    'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
                ], 500);
            }
        }

        // Récupérer tous les articles pour la sélection
        $articles = $articleRepository->findAll();
        // Récupérer les détails existants
        $details = $demandeAchat->getDemandeDetails();

        return $this->render('demand_achat/edit.html.twig', [
            'demande' => $demandeAchat,
            'articles' => $articles,
            'details' => $details,
            'activeLink' => 'demande_achat',
        ]);
    }

    #[Route('/demande/achat/{id}/status', name: 'app_demand_achat_status', methods: ['POST'])]
    public function changeStatus(Request $request, DemandeAchat $demandeAchat, EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['etat'])) {
            return $this->json(['success' => false, 'message' => 'Aucun état fourni'], 400);
        }
        $demandeAchat->setEtat($data['etat']);
        $entityManager->flush();
        return $this->json(['success' => true, 'etat' => $demandeAchat->getEtat()]);
    }

    #[Route('/demande/achat/{id}/pdf', name: 'app_demand_achat_pdf', methods: ['GET'])]
    public function generatePdf(DemandeAchat $demandeAchat): Response
    {
        return $this->json([
            'success' => true,
            'message' => 'La génération de PDF sera implémentée prochainement.',
            'demande_id' => $demandeAchat->getId()
        ]);
        
      
    }
}
