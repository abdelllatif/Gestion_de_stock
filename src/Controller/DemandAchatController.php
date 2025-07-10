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
use Spipu\Html2Pdf\Html2Pdf;

final class DemandAchatController extends AbstractController
{
    #[Route('/demande/achat', name: 'app_demand_achat')]
    public function index(DemandeAchatRepository $demandeAchatRepository): Response
    {   
        $demandes = $demandeAchatRepository->findAll();
        // dd($demandes);
        
        return $this->render('demand_achat/index.html.twig', [
            'demandes' => $demandes,
            'activeLink' => 'demande_achat',
        ]);
    }

    #[Route('/demande/achat/nouvelle', name: 'app_demand_achat_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, ArticleRepository $articleRepository): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $data = json_decode($request->getContent(), true);
                
                if (!isset($data['tva'])) {
                    return $this->json(['success' => false, 'message' => 'Données incomplètes: TVA manquante'], 400);
                }
                
                if ((empty($data['existingArticles']) || count($data['existingArticles']) === 0) && 
                    (empty($data['newArticles']) || count($data['newArticles']) === 0)) {
                    return $this->json(['success' => false, 'message' => 'Aucun article dans la demande'], 400);
                }
                
                $demandeAchat = new DemandeAchat();
                $demandeAchat->setDate(new \DateTime('now'));
                $demandeAchat->setEtat('En attente');
                $demandeAchat->setTva($data['tva']);
                
                $montantHT = 0;
                
                if (!empty($data['newArticles'])) {
                    foreach ($data['newArticles'] as $newArticleData) {

                        $data = new Article();
                        $data->setReference($newArticleData['reference']);
                        $data->setNom($newArticleData['nom']);
                        $data->setMarque($newArticleData['marque'] ?? '');
                        $data->setUnite($newArticleData['unite'] ?? 'Unité');
                        $data->setType($newArticleData['type'] ?? 'Consommable');
                        $data->setDescription($newArticleData['description'] ?? '');
                        $data->setPrix($newArticleData['prix']);
                        
                        $entityManager->persist($data);
                        
                        $demandeDetail = new DemandeDetails();
                        $demandeDetail->setArticle($data);
                        $demandeDetail->setQuantite($newArticleData['quantite']);
                        $demandeDetail->setPrixUnitaire($newArticleData['prix']);
                        $demandeDetail->setFournisseur($newArticleData['fournisseur'] ?? '');
                        
                        $prixTotal = $newArticleData['prix'] * $newArticleData['quantite'];
                        $demandeDetail->setPrixTotal($prixTotal);
                        
                        $demandeDetail->setDemandeAchat($demandeAchat);
                        $entityManager->persist($demandeDetail);
                        
                        $montantHT += $prixTotal;
                    }
                }
                

                if (!empty($data['existingArticles'])) {
                    foreach ($data['existingArticles'] as $existingArticleData) {
                        $data = $articleRepository->find($existingArticleData['articleId']);
                        
                        if (!$data) {
                            continue;
                        }
                        
                        // Créer les détails de demande
                        $demandeDetail = new DemandeDetails();
                        $demandeDetail->setArticle($data);
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
    public function getDemandeData(DemandeAchat $demandeAchat): Response
    {
        // Récupérer les détails de la demande
        $details = [];
        foreach ($demandeAchat->getDemandeDetails() as $detail) {
            $details[] = [
                'id' => $detail->getId(),
                'article' => $detail->getArticle() ? [
                    'id' => $detail->getArticle()->getId(),
                    'nom' => $detail->getArticle()->getNom(),
                    'reference' => $detail->getArticle()->getReference(),
                ] : null,
                'quantite' => $detail->getQuantite(),
                'prixUnitaire' => $detail->getPrixUnitaire(),
                'total' => $detail->getQuantite() * $detail->getPrixUnitaire(),
            ];
        }

        // Formater les données pour le PDF
        return $this->json([
            'id' => $demandeAchat->getId(),
            'date' => $demandeAchat->getDate()->format('Y-m-d'),
            'utilisateur' => $demandeAchat->getUilisateur() ? [
                'id' => $demandeAchat->getUilisateur()->getId(),
                'nom' => $demandeAchat->getUilisateur()->getNom(),
                'prenom' => $demandeAchat->getUilisateur()->getPrenom(),
            ] : null,
            'etat' => $demandeAchat->getEtat(),
            'montantHT' => $demandeAchat->getMontantHT(),
            'tva' => $demandeAchat->getTva(),
            'montantTTC' => $demandeAchat->getMontantTTC(),
            'details' => $details,
        ]);
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

        $articles = $articleRepository->findAll();
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
        // Créer une instance de HTML2PDF
        $html2pdf = new Html2Pdf('P', 'A4', 'fr');
        
        // Récupérer les détails de la demande
        $details = [];
        foreach ($demandeAchat->getDemandeDetails() as $detail) {
            $details[] = [
                'id' => $detail->getId(),
                'article' => $detail->getArticle() ? [
                    'nom' => $detail->getArticle()->getNom(),
                    'reference' => $detail->getArticle()->getReference(),
                ] : ['nom' => 'N/A', 'reference' => 'N/A'],
                'quantite' => $detail->getQuantite(),
                'prixUnitaire' => $detail->getPrixUnitaire(),
                'prixTotal' => $detail->getPrixTotal(),
            ];
        }
        
        // Préparer les données pour le template
        $dateFormattee = $demandeAchat->getDate() ? $demandeAchat->getDate()->format('d/m/Y') : date('d/m/Y');
        $userName = $demandeAchat->getUilisateur() ? 
            $demandeAchat->getUilisateur()->getNom() . ' ' . $demandeAchat->getUilisateur()->getPrenom() : 'N/A';
        
        // Créer le HTML pour le PDF
        $html = $this->renderView('demand_achat/pdf_template.html.twig', [
            'demande' => $demandeAchat,
            'details' => $details,
            'dateFormattee' => $dateFormattee,
            'userName' => $userName,
            'dateGeneration' => date('d/m/Y')
        ]);
        
        try {
            // Générer le PDF
            $html2pdf->writeHTML($html);
            
            // Nommer le fichier
            $filename = 'demande_achat_' . $demandeAchat->getId() . '.pdf';
            
            // Retourner le PDF en tant que réponse
            return new Response(
                $html2pdf->output($filename, 'S'),
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"'
                ]
            );
            
        } catch (\Exception $e) {
            // En cas d'erreur, retourner une réponse JSON avec le message d'erreur
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF: ' . $e->getMessage(),
                'demande_id' => $demandeAchat->getId()
            ], 500);
        }
    }
}
