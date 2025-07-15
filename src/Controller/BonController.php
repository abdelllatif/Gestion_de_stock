<?php

namespace App\Controller;

use App\Entity\Bon;
use App\Entity\BonDetails;
use App\Entity\Article;
use App\Entity\Machine;
use App\Entity\MouvementStock;
use App\Repository\BonRepository;
use App\Repository\ArticleRepository;
use App\Repository\ChantierRepository;
use App\Repository\MachineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class BonController extends AbstractController
{
    #[Route('/api/articles', name: 'api_articles')]
    public function getArticles(ArticleRepository $articleRepository): JsonResponse
    {
        $articles = $articleRepository->findAll();
        $data = [];
        
        foreach ($articles as $article) {
            $data[] = [
                'id' => $article->getId(),
                'nom' => $article->getNom(),
                'reference' => $article->getReference(),
                'marque' => $article->getMarque(),
                'unite' => $article->getUnite()
            ];
        }
        
        return $this->json($data);
    }
    
    #[Route('/api/machines', name: 'api_machines')]
    public function getMachines(MachineRepository $machineRepository): JsonResponse
    {
        $machines = $machineRepository->findAll();
        $data = [];
        
        foreach ($machines as $machine) {
            $data[] = [
                'id' => $machine->getId(),
                'nom' => $machine->getNom(),
                'code' => $machine->getCode()
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/bon', name: 'app_bon')]
    public function index(BonRepository $bonRepository, Request $request): Response
    {
        // Gestion de la requête AJAX pour DataTables
        if ($request->isXmlHttpRequest()) {
            try {
                $draw = $request->query->getInt('draw', 1);
                $start = $request->query->getInt('start', 0);
                $length = $request->query->getInt('length', 10);
                $search = $request->query->all('search')['value'] ?? '';
                $orders = $request->query->all('order');
                
                // Colonnes pour l'ordre
                $columns = [
                    'id',
                    'numeroSerie',
                    'type',
                    'date',
                    'chantier',
                    'details',
                    'actions'
                ];
                
                // Construction de l'ordre
                $orderColumnIdx = $orders[0]['column'] ?? 3;
                $orderColumn = $columns[$orderColumnIdx] ?? 'date';
                $orderDir = ($orders[0]['dir'] ?? 'desc') === 'desc' ? 'DESC' : 'ASC';
                
                // Récupération des données avec filtre, ordre et pagination
                $results = $bonRepository->findForDataTable($search, $orderColumn, $orderDir, $start, $length);
                $totalBons = $bonRepository->countTotal();
                $filteredBons = $bonRepository->countFiltered($search);
                
                // Formater les données pour DataTables
                $data = [];
                foreach ($results as $bon) {
                    $detailsCount = count($bon->getBonDetails());
                    
                    $data[] = [
                        'id' => $bon->getId(),
                        'numeroSerie' => $bon->getNumeroSerie(),
                        'type' => $bon->getType(),
                        'date' => $bon->getDate()->format('d/m/Y'),
                        'chantier' => $bon->getChantier() ? $bon->getChantier()->getNom() : 'N/A',
                        'details' => $detailsCount,
                        'actions' => $this->renderView('bon/_actions.html.twig', ['bon' => $bon])
                    ];
                }
                
                return $this->json([
                    'draw' => $draw,
                    'recordsTotal' => $totalBons,
                    'recordsFiltered' => $filteredBons,
                    'data' => $data
                ]);
            } catch (\Exception $e) {
                return $this->json([
                    'error' => true,
                    'message' => $e->getMessage()
                ], 400);
            }
        }
        
        // Affichage de la page principale
        return $this->render('bon/index.html.twig', [
            'activeLink' => 'bon',
        ]);
    }
    
    #[Route('/bon/nouveau', name: 'app_bon_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager, 
        ArticleRepository $articleRepository, 
        MachineRepository $machineRepository, 
        ChantierRepository $chantierRepository, 
        SessionInterface $session
    ): Response
    {
        // Si c'est une requête GET, afficher le formulaire
        if ($request->isMethod('GET')) {
            return $this->render('bon/new.html.twig', [
                'activeLink' => 'bon',
            ]);
        }
        
        // Traitement de la requête POST (AJAX)
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
                
                // Convertir la date du format dd/mm/yyyy au format Y-m-d
                $dateStr = $data['date'] ?? 'now';
                if ($dateStr !== 'now') {
                    $dateParts = explode('/', $dateStr);
                    if (count($dateParts) === 3) {
                        $dateStr = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
                    }
                }
                $bon->setDate(new \DateTime($dateStr));
                
                // Récupérer le chantier depuis la session
                $selectedChantier = $session->get('selected_chantier');
                if ($selectedChantier && isset($selectedChantier['id'])) {
                    $chantier = $chantierRepository->find($selectedChantier['id']);
                    if ($chantier) {
                        $bon->setChantier($chantier);
                    }
                }
                
                // Utiliser le chantier_id fourni dans les données si disponible
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
                        $bonDetail->setArticle($article);
                        $bonDetail->setQuantite($articleData['quantite']);
                        $bonDetail->setFournisseur($articleData['fournisseur'] ?? '');
                        $bonDetail->setUnite($article->getUnite() ?? 'Unité');
                        $bonDetail->setBon($bon);
                        
                        // Gestion du stock selon le type de bon
                        $this->updateStockForBonDetail($entityManager, $bon, $article, $articleData['quantite']);
                        
                        $entityManager->persist($bonDetail);
                    }
                }
                
                // Traiter les machines du bon
                if (!empty($data['machines'])) {
                    foreach ($data['machines'] as $machineData) {
                        // Vérifier si la machine existe
                        $machine = $machineRepository->find($machineData['id']);
                        if (!$machine) {
                            continue;
                        }
                        
                        $bonDetail = new BonDetails();
                        $bonDetail->setMachine($machine);
                        $bonDetail->setQuantite($machineData['quantite']);
                        $bonDetail->setFournisseur($machineData['fournisseur'] ?? '');
                        $bonDetail->setUnite('Unité'); // Par défaut pour les machines
                        $bonDetail->setBon($bon);
                        
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
        return $this->render('bon/new.html.twig', [
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
    public function delete(Bon $bon, EntityManagerInterface $entityManager, SessionInterface $session): JsonResponse
    {
        try {
            // Stocker les données du bon dans la session pour une éventuelle restauration
            $bonData = [
                'id' => $bon->getId(),
                'numeroSerie' => $bon->getNumeroSerie(),
                'type' => $bon->getType(),
                'date' => $bon->getDate()->format('Y-m-d H:i:s'),
                'note' => $bon->getNote(),
                'chantier_id' => $bon->getChantier() ? $bon->getChantier()->getId() : null,
                // Stocker les détails du bon
                'details' => []
            ];
            
            // Parcourir les détails pour les stocker
            foreach ($bon->getBonDetails() as $detail) {
                $detailData = [
                    'id' => $detail->getId(),
                    'quantite' => $detail->getQuantite(),
                    'article_id' => ($detail->getArticle() !== null) ? $detail->getArticle()->getId() : null,
                    'machine_id' => ($detail->getMachine() !== null) ? $detail->getMachine()->getId() : null
                ];
                $bonData['details'][] = $detailData;
                
                // Logique de mise à jour du stock à implémenter
            }
            
            // Sauvegarder les données dans la session
            $session->set('deleted_bon', $bonData);
            
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
    
    #[Route('/bon/{id}/restore', name: 'app_bon_restore', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function restore(int $id, EntityManagerInterface $entityManager, SessionInterface $session, ChantierRepository $chantierRepository, ArticleRepository $articleRepository, MachineRepository $machineRepository): JsonResponse
    {
        // Récupérer les données du bon supprimé depuis la session
        $bonData = $session->get('deleted_bon');
        
        if (!$bonData || $bonData['id'] != $id) {
            return $this->json([
                'success' => false,
                'message' => 'Impossible de restaurer ce bon. Les informations ne sont plus disponibles.'
            ], 404);
        }
        
        try {
            // Recréer le bon
            $bon = new Bon();
            $bon->setNumeroSerie($bonData['numeroSerie']);
            $bon->setType($bonData['type']);
            $bon->setDate(new \DateTime($bonData['date']));
            $bon->setNote($bonData['note']);
            
            // Rattacher le chantier
            if (!empty($bonData['chantier_id'])) {
                $chantier = $chantierRepository->find($bonData['chantier_id']);
                if ($chantier) {
                    $bon->setChantier($chantier);
                }
            }
            
            $entityManager->persist($bon);
            
            // Recréer les détails du bon
            foreach ($bonData['details'] as $detailData) {
                $detail = new BonDetails();
                $detail->setQuantite($detailData['quantite']);
                $detail->setBon($bon);
                
                // Rattacher l'article si présent
                if (!empty($detailData['article_id'])) {
                    $article = $articleRepository->find($detailData['article_id']);
                    if ($article) {
                        $detail->setArticle($article);
                    }
                }
                
                // Rattacher la machine si présente
                if (!empty($detailData['machine_id'])) {
                    $machine = $machineRepository->find($detailData['machine_id']);
                    if ($machine) {
                        $detail->setMachine($machine);
                    }
                }
                
                $entityManager->persist($detail);
                
                // Logique de mise à jour du stock à implémenter (inverse de la suppression)
            }
            
            $entityManager->flush();
            
            // Supprimer les données de la session
            $session->remove('deleted_bon');
            
            return $this->json([
                'success' => true,
                'message' => 'Bon restauré avec succès'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la restauration: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Met à jour le stock en fonction du type de bon et de l'article
     */
    private function updateStockForBonDetail(EntityManagerInterface $entityManager, Bon $bon, Article $article, int $quantite): void
    {
        $chantier = $bon->getChantier();
        
        // Si pas de chantier, on ne peut pas gérer le stock
        if (!$chantier) {
            return;
        }
        
        // Créer un mouvement de stock pour tracer cette opération
        $mouvement = new MouvementStock();
        $mouvement->setArticle($article);
        $mouvement->setQuantite($quantite);
        $mouvement->setType($bon->getType());
        $mouvement->setDate(new \DateTime());
        $mouvement->setFournisseur('');
        $mouvement->setObservation('Mouvement généré par le bon ' . $bon->getNumeroSerie());
        
        switch ($bon->getType()) {
            case 'Entrée':
                // Pour une entrée, le chantier de réception est le chantier du bon
                $mouvement->setChantierRec($chantier);
                break;
                
            case 'Sortie':
                // Pour une sortie, le chantier d'expédition est le chantier du bon
                $mouvement->setChantierExp($chantier);
                break;
                
            case 'Transfert':
                // Pour un transfert, le chantier d'expédition est le chantier du bon
                $mouvement->setChantierExp($chantier);
                // Note: Le chantier de réception devrait être spécifié dans une future implémentation
                break;
        }
        
        $entityManager->persist($mouvement);
    }
    
    /**
     * Génère un PDF pour un bon spécifique
     */
    #[Route('/bon/{id}/pdf', name: 'app_bon_pdf', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function generatePdf(Bon $bon): Response
    {
        // Création du contenu HTML pour le PDF
        $html = $this->renderView('bon/pdf.html.twig', [
            'bon' => $bon,
        ]);
        
        // Création du PDF avec configuration appropriée (simplifié)
        $pdf = new \Spipu\Html2Pdf\Html2Pdf(
            'P',                // Orientation: P=Portrait, L=Landscape
            'A4',               // Format du papier
            'fr'                // Langue
        );
        
        try {
            // Définition de la police par défaut
            $pdf->setDefaultFont('DejaVu Sans');
            
            // Écriture du contenu HTML
            $pdf->writeHTML($html);
            
            // Génération du nom de fichier
            $filename = 'bon_' . $bon->getNumeroSerie() . '_' . $bon->getDate()->format('Y-m-d') . '.pdf';
            
            // Renvoi du PDF au navigateur
            return new Response(
                $pdf->output($filename, 'S'),
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $filename . '"'
                ]
            );
        } catch (\Spipu\Html2Pdf\Exception\Html2PdfException $e) {
            // Log de l'erreur détaillée
            $html2pdf_error = $e->getMessage();
            
            return $this->render('error/pdf_error.html.twig', [
                'error' => $html2pdf_error,
                'bon_id' => $bon->getId(),
                'bon_numero' => $bon->getNumeroSerie()
            ], new Response(null, 500));
        } catch (\Exception $e) {
            // Attraper toute autre erreur
            $error = $e->getMessage();
            
            return $this->render('error/pdf_error.html.twig', [
                'error' => $error,
                'bon_id' => $bon->getId(),
                'bon_numero' => $bon->getNumeroSerie()
            ], new Response(null, 500));
        }
    }
}
