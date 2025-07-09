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
    public function index(ChantierRepository $chantierRepository): Response
    {   
        $activeLink = 'demande_achat';
        $chantiers = $chantierRepository->findAll();
        return $this->render('demand_achat/index.html.twig', [
            'controller_name' => 'DemandAchatController',
            'activeLink' => 'demand_achat',
            'chantiers' => $chantiers,
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
        // Retourner une simple réponse JSON pour le moment
        return $this->json([
            'success' => true,
            'message' => 'La génération de PDF sera implémentée prochainement.',
            'demande_id' => $demandeAchat->getId()
        ]);
        
        /* 
        // Exemple d'implémentation avec Dompdf (nécessite l'installation du bundle)
        
        $html = $this->renderView('demand_achat/pdf.html.twig', [
            'demande' => $demandeAchat,
        ]);
        
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $response = new Response($dompdf->output());
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="demande-achat-'.$demandeAchat->getId().'.pdf"');
        
        return $response;
        */
    }
}
