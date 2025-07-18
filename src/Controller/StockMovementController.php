<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\MouvementStockRepository;
use App\Repository\ArticleRepository;
use App\Repository\MachineRepository;
use App\Repository\ChantierRepository;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class StockMovementController extends AbstractController
{
    #[Route('/stock/movement', name: 'article_movement', methods: ['GET'])]
    public function list(
        MouvementStockRepository $mouvementStockRepository,
        ArticleRepository $articleRepository,
        MachineRepository $machineRepository,
        ChantierRepository $chantierRepository,
        StockRepository $stockRepository,
        Request $request
    ): Response {
         $session = $request->getSession();
        $selectedChantier = $session->get('selected_chantier');
        $chantierId = $selectedChantier['id'] ?? null;

        $mouvements = $mouvementStockRepository->createQueryBuilder('m')
            ->where('m.chantierExp = :chantierId')
            ->orWhere('m.chantierRec = :chantierId')
            ->setParameter('chantierId', $chantierId)
            ->getQuery()
            ->getResult();
        $articles = $articleRepository->findAll();
        $machines = $machineRepository->findAll();
        $chantiers = $chantierRepository->findAll();
        $session = $request->getSession();
        $selectedChantier = $session->get('selected_chantier');
        $sessionChantier = [
            'id' => $selectedChantier['id'] ?? null,
            'nom' => $selectedChantier['nom'] ?? null,
        ];
        $currentChantierStocks = [];
        if ($sessionChantier['id']) {
            $currentChantierStocks = $stockRepository->findAllStockForChantier($sessionChantier['id']);
        }
        $stockArticleIds = [];
        $stockMachineIds = [];
        foreach ($currentChantierStocks as $stock) {
            if (method_exists($stock, 'getArticle') && $stock->getArticle()) {
                $stockArticleIds[] = $stock->getArticle()->getId();
            } elseif (method_exists($stock, 'getMachine') && $stock->getMachine()) {
                $stockMachineIds[] = $stock->getMachine()->getId();
            }
        }

        $mouvementsWithDisplayType = [];
        foreach ($mouvements as $mouvement) {
            $displayType = $mouvement->getType();
            $canEdit = false;
            if ($mouvement->getType() === 'Transfert') {
                if ($sessionChantier['id'] && $mouvement->getChantierRec() && $mouvement->getChantierRec()->getId() == $sessionChantier['id']) {
                    $displayType = 'Entrée';
                    $canEdit = true;
                } elseif ($sessionChantier['id'] && $mouvement->getChantierExp() && $mouvement->getChantierExp()->getId() == $sessionChantier['id']) {
                    $displayType = 'Sortie';
                    $canEdit = true;
                }
            } elseif ($mouvement->getType() === 'Augmenter stock' && $mouvement->getChantierRec()) {
                $displayType = 'Augmenter stock';
                if ($sessionChantier['id'] && $mouvement->getChantierRec()->getId() == $sessionChantier['id']) {
                    $canEdit = true;
                }
            }
            $mouvementArray = [
                'id' => $mouvement->getId(),
                'type' => $mouvement->getType(),
                'displayType' => $displayType,
                'canEdit' => $canEdit,
                'date' => $mouvement->getDate(),
                'article' => $mouvement->getArticle() ? [
                    'id' => $mouvement->getArticle()->getId(),
                    'nom' => $mouvement->getArticle()->getNom(),
                ] : null,
                'machine' => $mouvement->getMachine() ? [
                    'id' => $mouvement->getMachine()->getId(),
                    'nom' => $mouvement->getMachine()->getNom(),
                ] : null,
                'quantite' => $mouvement->getQuantite(),
                'chantierExp' => $mouvement->getChantierExp() ? [
                    'id' => $mouvement->getChantierExp()->getId(),
                    'nom' => $mouvement->getChantierExp()->getNom(),
                ] : null,
                'chantierRec' => $mouvement->getChantierRec() ? [
                    'id' => $mouvement->getChantierRec()->getId(),
                    'nom' => $mouvement->getChantierRec()->getNom(),
                ] : null,
                'status' => $mouvement->getStatus(),
                'bonEtat' => $mouvement->getBonEtat(),
                'mauvaisEtat' => $mouvement->getMauvaisEtat(),
                'ferailleEtat' => $mouvement->getFerailleEtat(),
                'fournisseur' => $mouvement->getFournisseur(),
                'observation' => $mouvement->getObservation(),
            ];
            $mouvementsWithDisplayType[] = $mouvementArray;
        }

        return $this->render('stock_movement/index.html.twig', [
            'activeLink' => 'stock_movement',
            'mouvements' => $mouvementsWithDisplayType,
            'sessionChantier' => $sessionChantier,
            'articles' => array_map(function ($article) {
                return ['id' => $article->getId(), 'nom' => $article->getNom()];
            }, $articles),
            'machines' => array_map(function ($machine) {
                return ['id' => $machine->getId(), 'nom' => $machine->getNom()];
            }, $machines),
            'chantiers' => array_map(function ($chantier) {
                return ['id' => $chantier->getId(), 'nom' => $chantier->getNom()];
            }, $chantiers),
            'currentChantierStocks' => $currentChantierStocks,
            'stockArticleIds' => $stockArticleIds,
            'stockMachineIds' => $stockMachineIds,
        ]);
    }

    // Add this helper method to the controller
    private function mouvementToArray($mouvement, $sessionChantierId = null)
    {
        $displayType = $mouvement->getType();
        $canEdit = false;
        if ($mouvement->getType() === 'Transfert') {
            if ($sessionChantierId && $mouvement->getChantierRec() && $mouvement->getChantierRec()->getId() == $sessionChantierId) {
                $displayType = 'Entrée';
                $canEdit = true;
            } elseif ($sessionChantierId && $mouvement->getChantierExp() && $mouvement->getChantierExp()->getId() == $sessionChantierId) {
                $displayType = 'Sortie';
                $canEdit = true;
            }
        } elseif ($mouvement->getType() === 'Augmenter stock' && $mouvement->getChantierRec()) {
            $displayType = 'Augmenter stock';
            if ($sessionChantierId && $mouvement->getChantierRec()->getId() == $sessionChantierId) {
                $canEdit = true;
            }
        }
        return [
            'id' => $mouvement->getId(),
            'type' => $mouvement->getType(),
            'displayType' => $displayType,
            'canEdit' => $canEdit,
            'date' => $mouvement->getDate() ? $mouvement->getDate()->format('Y-m-d') : '',
            'article' => $mouvement->getArticle() ? [
                'id' => $mouvement->getArticle()->getId(),
                'nom' => $mouvement->getArticle()->getNom(),
            ] : null,
            'machine' => $mouvement->getMachine() ? [
                'id' => $mouvement->getMachine()->getId(),
                'nom' => $mouvement->getMachine()->getNom(),
            ] : null,
            'quantite' => $mouvement->getQuantite(),
            'chantierExp' => $mouvement->getChantierExp() ? [
                'id' => $mouvement->getChantierExp()->getId(),
                'nom' => $mouvement->getChantierExp()->getNom(),
            ] : null,
            'chantierRec' => $mouvement->getChantierRec() ? [
                'id' => $mouvement->getChantierRec()->getId(),
                'nom' => $mouvement->getChantierRec()->getNom(),
            ] : null,
            'status' => $mouvement->getStatus(),
            'bonEtat' => $mouvement->getBonEtat(),
            'mauvaisEtat' => $mouvement->getMauvaisEtat(),
            'ferailleEtat' => $mouvement->getFerailleEtat(),
            'fournisseur' => $mouvement->getFournisseur(),
            'observation' => $mouvement->getObservation(),
        ];
    }

    #[Route('/stock_movement/new', name: 'app_stock_movement_create', methods: ['POST'])]
    public function create(
        Request $request,
        ArticleRepository $articleRepository,
        MachineRepository $machineRepository,
        ChantierRepository $chantierRepository,
        StockRepository $stockRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = $request->request->all();
        $typeMouvement = $data['type'] ?? null;
        $itemType = $data['itemType'] ?? null;
        $itemIdRaw = $data['itemId'] ?? null;
        $quantite = (int)($data['quantite'] ?? 0);
        $bonEtat = (int)($data['bonEtat'] ?? 0);
        $mauvaisEtat = (int)($data['mauvaisEtat'] ?? 0);
        $ferailleEtat = (int)($data['ferailleEtat'] ?? 0);
        $fournisseur = $data['fournisseur'] ?? null;
        $chantierRecId = $data['chantierRec'] ?? null;
        $observation = $data['observation'] ?? null;
        $session = $request->getSession();
        $selectedChantier = $session->get('selected_chantier');
        $chantierActuelId = $selectedChantier['id'] ?? null;

        if (!$typeMouvement || !$itemIdRaw || !$quantite || !$chantierActuelId) {
            return new JsonResponse(['success' => false, 'message' => 'Paramètres manquants.'], 400);
        }

        if ($itemType === 'article' && ($bonEtat + $mauvaisEtat + $ferailleEtat) !== $quantite) {
            return new JsonResponse(['success' => false, 'message' => 'La somme des états doit être égale à la quantité.'], 400);
        }

        $mouvement = new \App\Entity\MouvementStock();
        $mouvement->setType($typeMouvement);
        $mouvement->setQuantite($quantite);
        $mouvement->setDate(new \DateTime());
        $mouvement->setFournisseur($fournisseur);
        $mouvement->setObservation($observation);
        $mouvement->setStatus('waiting');

        $isArticle = str_starts_with($itemIdRaw, 'article_');
        $isMachine = str_starts_with($itemIdRaw, 'machine_');
        if ($isArticle) {
            $itemId = (int)str_replace('article_', '', $itemIdRaw);
            $article = $articleRepository->find($itemId);
            if (!$article) {
                return new JsonResponse(['success' => false, 'message' => 'Article non trouvé.'], 404);
            }
            $mouvement->setArticle($article);
            $mouvement->setBonEtat($bonEtat);
            $mouvement->setMauvaisEtat($mauvaisEtat);
            $mouvement->setFerailleEtat($ferailleEtat);
        } elseif ($isMachine) {
            $itemId = (int)str_replace('machine_', '', $itemIdRaw);
            $machine = $machineRepository->find($itemId);
            if (!$machine) {
                return new JsonResponse(['success' => false, 'message' => 'Machine non trouvée.'], 404);
            }
            $mouvement->setMachine($machine);
        } else {
            return new JsonResponse(['success' => false, 'message' => 'Type d\'élément invalide.'], 400);
        }

        if ($typeMouvement === 'Augmenter stock') {
            $chantier = $chantierRepository->find($chantierActuelId);
            if (!$chantier) {
                return new JsonResponse(['success' => false, 'message' => 'Chantier actuel non trouvé.'], 404);
            }
            $mouvement->setChantierRec($chantier);
            $mouvement->setStatus('valid');
            if ($isArticle) {
                $stock = $stockRepository->findStockForArticleAndChantier($itemId, $chantierActuelId);
                if (!$stock) {
                    $stock = new \App\Entity\Stock();
                    $stock->setArticle($article);
                    $stock->setChantier($chantier);
                    $stock->setQuantiteChantier(0);
                    $stock->setBonEtat(0);
                    $stock->setMauvaisEtat(0);
                    $stock->setFerailleEtat(0);
                    $em->persist($stock);
                }
                $stock->setQuantiteChantier($stock->getQuantiteChantier() + $quantite);
                $stock->setBonEtat($stock->getBonEtat() + $bonEtat);
                $stock->setMauvaisEtat($stock->getMauvaisEtat() + $mauvaisEtat);
                $stock->setFerailleEtat($stock->getFerailleEtat() + $ferailleEtat);
            } elseif ($isMachine) {
                $stock = $stockRepository->findStockForMachineAndChantier($itemId, $chantierActuelId);
                if (!$stock) {
                    $stock = new \App\Entity\Stock();
                    $stock->setMachine($machine);
                    $stock->setChantier($chantier);
                    $stock->setQuantiteChantier(0);
                    $stock->setBonEtat(0);
                    $stock->setMauvaisEtat(0);
                    $stock->setFerailleEtat(0);
                    $em->persist($stock);
                }
                $stock->setQuantiteChantier($stock->getQuantiteChantier() + $quantite);
            }
        } elseif ($typeMouvement === 'Transfert') {
            if (!$chantierRecId) {
                return new JsonResponse(['success' => false, 'message' => 'Chantier destinataire manquant.'], 400);
            }
            $chantierExp = $chantierRepository->find($chantierActuelId);
            $chantierRec = $chantierRepository->find($chantierRecId);
            if (!$chantierExp || !$chantierRec) {
                return new JsonResponse(['success' => false, 'message' => 'Chantier(s) non trouvé(s).'], 404);
            }
            $mouvement->setChantierExp($chantierExp);
            $mouvement->setChantierRec($chantierRec);
        }

        try {
            $em->persist($mouvement);
            $em->flush();
            $session = $request->getSession();
            $selectedChantier = $session->get('selected_chantier');
            $sessionChantierId = $selectedChantier['id'] ?? null;
            return new JsonResponse([
                'success' => true,
                'message' => 'Mouvement créé en attente de validation.',
                'movement' => $this->mouvementToArray($mouvement, $sessionChantierId)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la création du mouvement: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/stock_movement/check_quantity', name: 'app_stock_movement_check_quantity', methods: ['POST'])]
    public function checkQuantity(Request $request, StockRepository $stockRepository): JsonResponse
    {
        $itemIdRaw = $request->request->get('itemId');
        $chantierId = $request->request->get('chantierId');
        if (!$itemIdRaw || !$chantierId) {
            return new JsonResponse(['success' => false, 'message' => 'Paramètres manquants.'], 400);
        }
        $isArticle = str_starts_with($itemIdRaw, 'article_');
        $isMachine = str_starts_with($itemIdRaw, 'machine_');
        if ($isArticle) {
            $itemId = (int)str_replace('article_', '', $itemIdRaw);
            $stock = $stockRepository->findStockForArticleAndChantier($itemId, $chantierId);
        } elseif ($isMachine) {
            $itemId = (int)str_replace('machine_', '', $itemIdRaw);
            $stock = $stockRepository->findStockForMachineAndChantier($itemId, $chantierId);
        } else {
            return new JsonResponse(['success' => false, 'message' => 'Type d\'élément invalide.'], 400);
        }
        $available = $stock ? $stock->getQuantiteChantier() : 0;
        return new JsonResponse(['success' => true, 'available' => $available]);
    }

    #[Route('/stock_movement/fetch_items', name: 'app_stock_movement_fetch_items', methods: ['POST'])]
    public function fetchItems(
        Request $request,
        ArticleRepository $articleRepository,
        MachineRepository $machineRepository,
        StockRepository $stockRepository
    ): JsonResponse {
        $type = $request->request->get('type');
        $itemType = $request->request->get('itemType');
        $session = $request->getSession();
        $selectedChantier = $session->get('selected_chantier');
        $chantierId = $selectedChantier['id'] ?? null;
        $results = [];

        if ($type === 'Transfert' && $chantierId) {
            $currentStocks = $stockRepository->findAllStockForChantier($chantierId);
            foreach ($currentStocks as $stock) {
                if ($itemType && $itemType === 'article' && method_exists($stock, 'getArticle') && $stock->getArticle()) {
                    $article = $stock->getArticle();
                    $results[] = [
                        'id' => 'article_' . $article->getId(),
                        'type' => 'article',
                        'name' => $article->getNom(),
                    ];
                } elseif ($itemType && $itemType === 'machine' && method_exists($stock, 'getMachine') && $stock->getMachine()) {
                    $machine = $stock->getMachine();
                    $results[] = [
                        'id' => 'machine_' . $machine->getId(),
                        'type' => 'machine',
                        'name' => $machine->getNom(),
                    ];
                } elseif (!$itemType) {
                    if (method_exists($stock, 'getArticle') && $stock->getArticle()) {
                        $article = $stock->getArticle();
                        $results[] = [
                            'id' => 'article_' . $article->getId(),
                            'type' => 'article',
                            'name' => $article->getNom(),
                        ];
                    } elseif (method_exists($stock, 'getMachine') && $stock->getMachine()) {
                        $machine = $stock->getMachine();
                        $results[] = [
                            'id' => 'machine_' . $machine->getId(),
                            'type' => 'machine',
                            'name' => $machine->getNom(),
                        ];
                    }
                }
            }
        } elseif ($type === 'Augmenter stock') {
            if (!$itemType || $itemType === 'article') {
                foreach ($articleRepository->findAll() as $article) {
                    $results[] = [
                        'id' => 'article_' . $article->getId(),
                        'type' => 'article',
                        'name' => $article->getNom(),
                    ];
                }
            }
            if (!$itemType || $itemType === 'machine') {
                foreach ($machineRepository->findAll() as $machine) {
                    $results[] = [
                        'id' => 'machine_' . $machine->getId(),
                        'type' => 'machine',
                        'name' => $machine->getNom(),
                    ];
                }
            }
        }
        return new JsonResponse(['success' => true, 'items' => $results]);
    }

    #[Route('/stock_movement/{id}/edit', name: 'app_stock_movement_edit', methods: ['GET'])]
    public function edit(int $id, MouvementStockRepository $mouvementStockRepository): JsonResponse
    {
        $mouvement = $mouvementStockRepository->find($id);
        if (!$mouvement) {
            return new JsonResponse(['success' => false, 'message' => 'Mouvement non trouvé.'], 404);
        }
        $data = [
            'id' => $mouvement->getId(),
            'type' => $mouvement->getType(),
            'itemType' => $mouvement->getArticle() ? 'article' : ($mouvement->getMachine() ? 'machine' : ''),
            'itemId' => $mouvement->getArticle() ? 'article_' . $mouvement->getArticle()->getId() : ($mouvement->getMachine() ? 'machine_' . $mouvement->getMachine()->getId() : ''),
            'quantite' => $mouvement->getQuantite(),
            'bonEtat' => $mouvement->getBonEtat() ?? 0,
            'mauvaisEtat' => $mouvement->getMauvaisEtat() ?? 0,
            'ferailleEtat' => $mouvement->getFerailleEtat() ?? 0,
            'chantierRec' => $mouvement->getChantierRec() ? $mouvement->getChantierRec()->getId() : null,
            'fournisseur' => $mouvement->getFournisseur(),
            'observation' => $mouvement->getObservation(),
            'status' => $mouvement->getStatus(),
        ];
        return new JsonResponse(['success' => true, 'data' => $data]);
    }

    #[Route('/stock_movement/edit', name: 'app_stock_movement_update', methods: ['POST'])]
    public function update(
        Request $request,
        MouvementStockRepository $mouvementStockRepository,
        ArticleRepository $articleRepository,
        MachineRepository $machineRepository,
        ChantierRepository $chantierRepository,
        StockRepository $stockRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = $request->request->all();
        $id = (int)($data['id'] ?? 0);
        $typeMouvement = $data['type'] ?? null;
        $itemType = $data['itemType'] ?? null;
        $itemIdRaw = $data['itemId'] ?? null;
        $quantite = (int)($data['quantite'] ?? 0);
        $bonEtat = (int)($data['bonEtat'] ?? 0);
        $mauvaisEtat = (int)($data['mauvaisEtat'] ?? 0);
        $ferailleEtat = (int)($data['ferailleEtat'] ?? 0);
        $fournisseur = $data['fournisseur'] ?? null;
        $chantierRecId = $data['chantierRec'] ?? null;
        $observation = $data['observation'] ?? null;
        $session = $request->getSession();
        $selectedChantier = $session->get('selected_chantier');
        $chantierActuelId = $selectedChantier['id'] ?? null;

        if (!$id || !$typeMouvement || !$itemIdRaw || !$quantite || !$chantierActuelId) {
            return new JsonResponse(['success' => false, 'message' => 'Paramètres manquants.'], 400);
        }

        if ($itemType === 'article' && ($bonEtat + $mauvaisEtat + $ferailleEtat) !== $quantite) {
            return new JsonResponse(['success' => false, 'message' => 'La somme des états doit être égale à la quantité.'], 400);
        }

        $mouvement = $mouvementStockRepository->find($id);
        if (!$mouvement) {
            return new JsonResponse(['success' => false, 'message' => 'Mouvement non trouvé.'], 404);
        }

        // Revert previous stock changes if the movement was valid
        if ($mouvement->getStatus() === 'valid') {
            $stock = $stockRepository->findStockForArticleAndChantier(
                $mouvement->getArticle() ? $mouvement->getArticle()->getId() : null,
                $mouvement->getChantierRec() ? $mouvement->getChantierRec()->getId() : $chantierActuelId
            ) ?: $stockRepository->findStockForMachineAndChantier(
                $mouvement->getMachine() ? $mouvement->getMachine()->getId() : null,
                $mouvement->getChantierRec() ? $mouvement->getChantierRec()->getId() : $chantierActuelId
            );
            if ($stock) {
                $stock->setQuantiteChantier($stock->getQuantiteChantier() - $mouvement->getQuantite());
                if ($mouvement->getArticle()) {
                    $stock->setBonEtat($stock->getBonEtat() - ($mouvement->getBonEtat() ?? 0));
                    $stock->setMauvaisEtat($stock->getMauvaisEtat() - ($mouvement->getMauvaisEtat() ?? 0));
                    $stock->setFerailleEtat($stock->getFerailleEtat() - ($mouvement->getFerailleEtat() ?? 0));
                }
            }
            if ($mouvement->getType() === 'Transfert' && $mouvement->getChantierExp()) {
                $stockExp = $stockRepository->findStockForArticleAndChantier(
                    $mouvement->getArticle() ? $mouvement->getArticle()->getId() : null,
                    $mouvement->getChantierExp()->getId()
                ) ?: $stockRepository->findStockForMachineAndChantier(
                    $mouvement->getMachine() ? $mouvement->getMachine()->getId() : null,
                    $mouvement->getChantierExp()->getId()
                );
                if ($stockExp) {
                    $stockExp->setQuantiteChantier($stockExp->getQuantiteChantier() + $mouvement->getQuantite());
                    if ($mouvement->getArticle()) {
                        $stockExp->setBonEtat($stockExp->getBonEtat() + ($mouvement->getBonEtat() ?? 0));
                        $stockExp->setMauvaisEtat($stockExp->getMauvaisEtat() + ($mouvement->getMauvaisEtat() ?? 0));
                        $stockExp->setFerailleEtat($stockExp->getFerailleEtat() + ($mouvement->getFerailleEtat() ?? 0));
                    }
                }
            }
        }

        $mouvement->setType($typeMouvement);
        $mouvement->setQuantite($quantite);
        $mouvement->setFournisseur($fournisseur);
        $mouvement->setObservation($observation);
        $mouvement->setDate(new \DateTime());
        $mouvement->setStatus('waiting');

        $isArticle = str_starts_with($itemIdRaw, 'article_');
        $isMachine = str_starts_with($itemIdRaw, 'machine_');
        if ($isArticle) {
            $itemId = (int)str_replace('article_', '', $itemIdRaw);
            $article = $articleRepository->find($itemId);
            if (!$article) {
                return new JsonResponse(['success' => false, 'message' => 'Article non trouvé.'], 404);
            }
            $mouvement->setArticle($article);
            $mouvement->setMachine(null);
            $mouvement->setBonEtat($bonEtat);
            $mouvement->setMauvaisEtat($mauvaisEtat);
            $mouvement->setFerailleEtat($ferailleEtat);
        } elseif ($isMachine) {
            $itemId = (int)str_replace('machine_', '', $itemIdRaw);
            $machine = $machineRepository->find($itemId);
            if (!$machine) {
                return new JsonResponse(['success' => false, 'message' => 'Machine non trouvée.'], 404);
            }
            $mouvement->setMachine($machine);
            $mouvement->setArticle(null);
            $mouvement->setBonEtat(null);
            $mouvement->setMauvaisEtat(null);
            $mouvement->setFerailleEtat(null);
        } else {
            return new JsonResponse(['success' => false, 'message' => 'Type d\'élément invalide.'], 400);
        }

        if ($typeMouvement === 'Augmenter stock') {
            $chantier = $chantierRepository->find($chantierActuelId);
            if (!$chantier) {
                return new JsonResponse(['success' => false, 'message' => 'Chantier actuel non trouvé.'], 404);
            }
            $mouvement->setChantierRec($chantier);
            $mouvement->setChantierExp(null);
            $mouvement->setStatus('valid');
            if ($isArticle) {
                $stock = $stockRepository->findStockForArticleAndChantier($itemId, $chantierActuelId);
                if (!$stock) {
                    $stock = new \App\Entity\Stock();
                    $stock->setArticle($article);
                    $stock->setChantier($chantier);
                    $stock->setQuantiteChantier(0);
                    $stock->setBonEtat(0);
                    $stock->setMauvaisEtat(0);
                    $stock->setFerailleEtat(0);
                    $em->persist($stock);
                }
                $stock->setQuantiteChantier($stock->getQuantiteChantier() + $quantite);
                $stock->setBonEtat($stock->getBonEtat() + $bonEtat);
                $stock->setMauvaisEtat($stock->getMauvaisEtat() + $mauvaisEtat);
                $stock->setFerailleEtat($stock->getFerailleEtat() + $ferailleEtat);
            } elseif ($isMachine) {
                $stock = $stockRepository->findStockForMachineAndChantier($itemId, $chantierActuelId);
                if (!$stock) {
                    $stock = new \App\Entity\Stock();
                    $stock->setMachine($machine);
                    $stock->setChantier($chantier);
                    $stock->setQuantiteChantier(0);
                    $stock->setBonEtat(0);
                    $stock->setMauvaisEtat(0);
                    $stock->setFerailleEtat(0);
                    $em->persist($stock);
                }
                $stock->setQuantiteChantier($stock->getQuantiteChantier() + $quantite);
            }
        } elseif ($typeMouvement === 'Transfert') {
            if (!$chantierRecId) {
                return new JsonResponse(['success' => false, 'message' => 'Chantier destinataire manquant.'], 400);
            }
            $chantierExp = $chantierRepository->find($chantierActuelId);
            $chantierRec = $chantierRepository->find($chantierRecId);
            if (!$chantierExp || !$chantierRec) {
                return new JsonResponse(['success' => false, 'message' => 'Chantier(s) non trouvé(s).'], 404);
            }
            $mouvement->setChantierExp($chantierExp);
            $mouvement->setChantierRec($chantierRec);
        }

        try {
            $em->persist($mouvement);
            $em->flush();
            $session = $request->getSession();
            $selectedChantier = $session->get('selected_chantier');
            $sessionChantierId = $selectedChantier['id'] ?? null;
            return new JsonResponse([
                'success' => true,
                'message' => 'Mouvement modifié.',
                'movement' => $this->mouvementToArray($mouvement, $sessionChantierId)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la modification du mouvement: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/stock_movement/delete/{id}', name: 'app_stock_movement_delete', methods: ['POST'])]
    public function delete(int $id, MouvementStockRepository $mouvementStockRepository, StockRepository $stockRepository, EntityManagerInterface $em): JsonResponse
    {
        $mouvement = $mouvementStockRepository->find($id);
        if (!$mouvement) {
            return new JsonResponse(['success' => false, 'message' => 'Mouvement non trouvé.'], 404);
        }

        if ($mouvement->getStatus() === 'valid') {
            $stock = $stockRepository->findStockForArticleAndChantier(
                $mouvement->getArticle() ? $mouvement->getArticle()->getId() : null,
                $mouvement->getChantierRec()->getId()
            ) ?: $stockRepository->findStockForMachineAndChantier(
                $mouvement->getMachine() ? $mouvement->getMachine()->getId() : null,
                $mouvement->getChantierRec()->getId()
            );
            if ($stock) {
                $stock->setQuantiteChantier($stock->getQuantiteChantier() - $mouvement->getQuantite());
                if ($mouvement->getArticle()) {
                    $stock->setBonEtat($stock->getBonEtat() - ($mouvement->getBonEtat() ?? 0));
                    $stock->setMauvaisEtat($stock->getMauvaisEtat() - ($mouvement->getMauvaisEtat() ?? 0));
                    $stock->setFerailleEtat($stock->getFerailleEtat() - ($mouvement->getFerailleEtat() ?? 0));
                }
            }
            if ($mouvement->getType() === 'Transfert') {
                $stockExp = $stockRepository->findStockForArticleAndChantier(
                    $mouvement->getArticle() ? $mouvement->getArticle()->getId() : null,
                    $mouvement->getChantierExp()->getId()
                ) ?: $stockRepository->findStockForMachineAndChantier(
                    $mouvement->getMachine() ? $mouvement->getMachine()->getId() : null,
                    $mouvement->getChantierExp()->getId()
                );
                if ($stockExp) {
                    $stockExp->setQuantiteChantier($stockExp->getQuantiteChantier() + $mouvement->getQuantite());
                    if ($mouvement->getArticle()) {
                        $stockExp->setBonEtat($stockExp->getBonEtat() + ($mouvement->getBonEtat() ?? 0));
                        $stockExp->setMauvaisEtat($stockExp->getMauvaisEtat() + ($mouvement->getMauvaisEtat() ?? 0));
                        $stockExp->setFerailleEtat($stockExp->getFerailleEtat() + ($mouvement->getFerailleEtat() ?? 0));
                    }
                }
            }
        }

        try {
            $em->remove($mouvement);
            $em->flush();
            return new JsonResponse(['success' => true, 'message' => 'Mouvement supprimé.']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la suppression du mouvement: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/stock_movement/change_status/{id}/{status}', name: 'app_stock_movement_change_status', methods: ['POST'])]
    public function changeStatus(
        int $id,
        string $status,
        MouvementStockRepository $mouvementStockRepository,
        StockRepository $stockRepository,
        ChantierRepository $chantierRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $mouvement = $mouvementStockRepository->find($id);
        if (!$mouvement) {
            return new JsonResponse(['success' => false, 'message' => 'Mouvement non trouvé.'], 404);
        }

        if (!in_array($status, ['valid', 'rejected'])) {
            return new JsonResponse(['success' => false, 'message' => 'Statut invalide.'], 400);
        }

        if ($mouvement->getStatus() === 'valid' && $mouvement->getType() === 'Transfert') {
            $stockExp = $stockRepository->findStockForArticleAndChantier(
                $mouvement->getArticle() ? $mouvement->getArticle()->getId() : null,
                $mouvement->getChantierExp()->getId()
            ) ?: $stockRepository->findStockForMachineAndChantier(
                $mouvement->getMachine() ? $mouvement->getMachine()->getId() : null,
                $mouvement->getChantierExp()->getId()
            );
            $stockRec = $stockRepository->findStockForArticleAndChantier(
                $mouvement->getArticle() ? $mouvement->getArticle()->getId() : null,
                $mouvement->getChantierRec()->getId()
            ) ?: $stockRepository->findStockForMachineAndChantier(
                $mouvement->getMachine() ? $mouvement->getMachine()->getId() : null,
                $mouvement->getChantierRec()->getId()
            );
            if ($stockExp) {
                $stockExp->setQuantiteChantier($stockExp->getQuantiteChantier() + $mouvement->getQuantite());
                if ($mouvement->getArticle()) {
                    $stockExp->setBonEtat($stockExp->getBonEtat() + ($mouvement->getBonEtat() ?? 0));
                    $stockExp->setMauvaisEtat($stockExp->getMauvaisEtat() + ($mouvement->getMauvaisEtat() ?? 0));
                    $stockExp->setFerailleEtat($stockExp->getFerailleEtat() + ($mouvement->getFerailleEtat() ?? 0));
                }
            }
            if ($stockRec) {
                $stockRec->setQuantiteChantier($stockRec->getQuantiteChantier() - $mouvement->getQuantite());
                if ($mouvement->getArticle()) {
                    $stockRec->setBonEtat($stockRec->getBonEtat() - ($mouvement->getBonEtat() ?? 0));
                    $stockRec->setMauvaisEtat($stockRec->getMauvaisEtat() - ($mouvement->getMauvaisEtat() ?? 0));
                    $stockRec->setFerailleEtat($stockRec->getFerailleEtat() - ($mouvement->getFerailleEtat() ?? 0));
                }
            }
        }

        if ($status === 'valid' && $mouvement->getType() === 'Transfert') {
            $chantierExpId = $mouvement->getChantierExp()->getId();
            $chantierRecId = $mouvement->getChantierRec()->getId();
            $itemId = $mouvement->getArticle() ? $mouvement->getArticle()->getId() : ($mouvement->getMachine() ? $mouvement->getMachine()->getId() : null);
            $isArticle = $mouvement->getArticle() !== null;

            $stockExp = $isArticle
                ? $stockRepository->findStockForArticleAndChantier($itemId, $chantierExpId)
                : $stockRepository->findStockForMachineAndChantier($itemId, $chantierExpId);
            if (!$stockExp || $stockExp->getQuantiteChantier() < $mouvement->getQuantite()) {
                return new JsonResponse(['success' => false, 'message' => 'Stock insuffisant pour le transfert.'], 400);
            }

            $stockExp->setQuantiteChantier($stockExp->getQuantiteChantier() - $mouvement->getQuantite());
            if ($isArticle) {
                $stockExp->setBonEtat($stockExp->getBonEtat() - ($mouvement->getBonEtat() ?? 0));
                $stockExp->setMauvaisEtat($stockExp->getMauvaisEtat() - ($mouvement->getMauvaisEtat() ?? 0));
                $stockExp->setFerailleEtat($stockExp->getFerailleEtat() - ($mouvement->getFerailleEtat() ?? 0));
            }

            $stockRec = $isArticle
                ? $stockRepository->findStockForArticleAndChantier($itemId, $chantierRecId)
                : $stockRepository->findStockForMachineAndChantier($itemId, $chantierRecId);
            if (!$stockRec) {
                $stockRec = new \App\Entity\Stock();
                if ($isArticle) {
                    $stockRec->setArticle($mouvement->getArticle());
                } else {
                    $stockRec->setMachine($mouvement->getMachine());
                }
                $stockRec->setChantier($mouvement->getChantierRec());
                $stockRec->setQuantiteChantier(0);
                $stockRec->setBonEtat(0);
                $stockRec->setMauvaisEtat(0);
                $stockRec->setFerailleEtat(0);
                $em->persist($stockRec);
            }
            $stockRec->setQuantiteChantier($stockRec->getQuantiteChantier() + $mouvement->getQuantite());
            if ($isArticle) {
                $stockRec->setBonEtat($stockRec->getBonEtat() + ($mouvement->getBonEtat() ?? 0));
                $stockRec->setMauvaisEtat($stockRec->getMauvaisEtat() + ($mouvement->getMauvaisEtat() ?? 0));
                $stockRec->setFerailleEtat($stockRec->getFerailleEtat() + ($mouvement->getFerailleEtat() ?? 0));
            }
        }

        $mouvement->setStatus($status);
        try {
            $em->persist($mouvement);
            $em->flush();
            return new JsonResponse(['success' => true, 'message' => 'Statut modifié avec succès.']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la modification du statut: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/stock_movement/export-excel', name: 'stock_movement_export_excel', methods: ['GET'])]
    public function exportExcel(
        Request $request,
        MouvementStockRepository $mouvementStockRepository,
        ChantierRepository $chantierRepository
    ): Response {
        $session = $request->getSession();
        $selectedChantier = $session->get('selected_chantier');
        $chantierId = $selectedChantier['id'] ?? null;
        $chantierNom = $selectedChantier['nom'] ?? 'Chantier';
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');

        if (!$chantierId) {
            $this->addFlash('error', 'Aucun chantier sélectionné.');
            return $this->redirectToRoute('article_movement');
        }

        $qb = $mouvementStockRepository->createQueryBuilder('m')
            ->where('m.chantierExp = :chantierId OR m.chantierRec = :chantierId')
            ->setParameter('chantierId', $chantierId);
        if ($startDate && $endDate) {
            $qb->andWhere('m.date BETWEEN :start AND :end')
                ->setParameter('start', $startDate.' 00:00:00')
                ->setParameter('end', $endDate.' 23:59:59');
        } elseif ($startDate) {
            $qb->andWhere('m.date >= :start')->setParameter('start', $startDate.' 00:00:00');
        } elseif ($endDate) {
            $qb->andWhere('m.date <= :end')->setParameter('end', $endDate.' 23:59:59');
        }
        $mouvements = $qb->getQuery()->getResult();

        // Prepare data for two sheets
        $machineMouvements = [];
        $articleMouvements = [];
        foreach ($mouvements as $m) {
            if ($m->getMachine()) {
                $machineMouvements[] = $m;
            } elseif ($m->getArticle()) {
                $articleMouvements[] = $m;
            }
        }
        $spreadsheet = new Spreadsheet();
        // --- Sheet 1: Machines ---
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Machines');
        if ($startDate && $endDate) {
            $title1 = 'Mouvements de stock (Machines) du chantier ' . $chantierNom . ' du ' . $startDate . ' au ' . $endDate;
        } else {
            $title1 = 'Mouvements de stock (Machines) du chantier ' . $chantierNom;
        }
        $sheet1->mergeCells('A1:G1');
        $sheet1->setCellValue('A1', $title1);
        $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(18);
        $sheet1->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('A1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1e3a8a');
        $sheet1->getStyle('A1')->getFont()->getColor()->setRGB('FFFFFF');
        $row = 2;
        $headers = ['Type', 'Date', 'Article/Machine', 'Quantité', 'Chantier Récepteur', 'Chantier Expéditeur', 'Fournisseur'];
        $sheet1->fromArray($headers, null, 'A' . $row);
        $sheet1->getStyle('A'.$row.':G'.$row)->getFont()->setBold(true)->setSize(13);
        $sheet1->getStyle('A'.$row.':G'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('A'.$row.':G'.$row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('3b82f6');
        $sheet1->getStyle('A'.$row.':G'.$row)->getFont()->getColor()->setRGB('FFFFFF');
        $row++;
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FF888888'],
                ],
            ],
        ];
        foreach ($machineMouvements as $m) {
            $type = $m->getType();
            $displayType = $type;
            if ($type === 'Transfert') {
                if ($m->getChantierRec() && $m->getChantierRec()->getId() == $chantierId) {
                    $displayType = 'Entrée';
                } elseif ($m->getChantierExp() && $m->getChantierExp()->getId() == $chantierId) {
                    $displayType = 'Sortie';
                }
            }
            $date = $m->getDate() ? $m->getDate()->format('Y-m-d') : '';
            $articleOrMachine = $m->getMachine() ? $m->getMachine()->getNom() : '-';
            $quantite = $m->getQuantite();
            $chantierExpediteur = '-';
            $chantierRecepteur = '-';
            if ($displayType === 'Entrée') {
                $chantierRecepteur = $chantierNom;
                $chantierExpediteur = $m->getChantierExp() ? $m->getChantierExp()->getNom() : '-';
            } elseif ($displayType === 'Sortie') {
                $chantierExpediteur = $chantierNom;
                $chantierRecepteur = $m->getChantierRec() ? $m->getChantierRec()->getNom() : '-';
            } elseif ($displayType === 'Augmenter stock') {
                $chantierRecepteur = $chantierNom;
                $chantierExpediteur = '-';
            }
            $fournisseur = $type === 'Augmenter stock' ? ($m->getFournisseur() ?: '-') : '-';
            $sheet1->fromArray([
                $displayType,
                $date,
                $articleOrMachine,
                $quantite,
                $chantierRecepteur,
                $chantierExpediteur,
                $fournisseur
            ], null, 'A' . $row);
            $sheet1->getStyle('A'.$row.':G'.$row)->getFont()->setSize(14);
            $sheet1->getStyle('A'.$row.':G'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle('A'.$row.':G'.$row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet1->getStyle('A'.$row.':G'.$row)->applyFromArray($borderStyle);
            $row++;
        }
        $sheet1->getColumnDimension('A')->setWidth(20);
        $sheet1->getColumnDimension('B')->setWidth(18);
        $sheet1->getColumnDimension('C')->setWidth(35);
        $sheet1->getColumnDimension('D')->setWidth(15);
        $sheet1->getColumnDimension('E')->setWidth(25);
        $sheet1->getColumnDimension('F')->setWidth(25);
        $sheet1->getColumnDimension('G')->setWidth(25);
        // --- Sheet 2: Articles ---
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Articles');
        if ($startDate && $endDate) {
            $title2 = 'Mouvements de stock (Articles) du chantier ' . $chantierNom . ' du ' . $startDate . ' au ' . $endDate;
        } else {
            $title2 = 'Mouvements de stock (Articles) du chantier ' . $chantierNom;
        }
        $sheet2->mergeCells('A1:G1');
        $sheet2->setCellValue('A1', $title2);
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(18);
        $sheet2->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('A1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1e3a8a');
        $sheet2->getStyle('A1')->getFont()->getColor()->setRGB('FFFFFF');
        $row = 2;
        $sheet2->fromArray($headers, null, 'A' . $row);
        $sheet2->getStyle('A'.$row.':G'.$row)->getFont()->setBold(true)->setSize(13);
        $sheet2->getStyle('A'.$row.':G'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('A'.$row.':G'.$row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('3b82f6');
        $sheet2->getStyle('A'.$row.':G'.$row)->getFont()->getColor()->setRGB('FFFFFF');
        $row++;
        foreach ($articleMouvements as $m) {
            $type = $m->getType();
            $displayType = $type;
            if ($type === 'Transfert') {
                if ($m->getChantierRec() && $m->getChantierRec()->getId() == $chantierId) {
                    $displayType = 'Entrée';
                } elseif ($m->getChantierExp() && $m->getChantierExp()->getId() == $chantierId) {
                    $displayType = 'Sortie';
                }
            }
            $date = $m->getDate() ? $m->getDate()->format('Y-m-d') : '';
            $articleOrMachine = $m->getArticle() ? $m->getArticle()->getNom() : '-';
            $quantite = $m->getQuantite();
            $chantierExpediteur = '-';
            $chantierRecepteur = '-';
            if ($displayType === 'Entrée') {
                $chantierRecepteur = $chantierNom;
                $chantierExpediteur = $m->getChantierExp() ? $m->getChantierExp()->getNom() : '-';
            } elseif ($displayType === 'Sortie') {
                $chantierExpediteur = $chantierNom;
                $chantierRecepteur = $m->getChantierRec() ? $m->getChantierRec()->getNom() : '-';
            } elseif ($displayType === 'Augmenter stock') {
                $chantierRecepteur = $chantierNom;
                $chantierExpediteur = '-';
            }
            $fournisseur = $type === 'Augmenter stock' ? ($m->getFournisseur() ?: '-') : '-';
            $sheet2->fromArray([
                $displayType,
                $date,
                $articleOrMachine,
                $quantite,
                $chantierRecepteur,
                $chantierExpediteur,
                $fournisseur
            ], null, 'A' . $row);
            $sheet2->getStyle('A'.$row.':G'.$row)->getFont()->setSize(14);
            $sheet2->getStyle('A'.$row.':G'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle('A'.$row.':G'.$row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet2->getStyle('A'.$row.':G'.$row)->applyFromArray($borderStyle);
            $row++;
        }
        $sheet2->getColumnDimension('A')->setWidth(20);
        $sheet2->getColumnDimension('B')->setWidth(18);
        $sheet2->getColumnDimension('C')->setWidth(35);
        $sheet2->getColumnDimension('D')->setWidth(15);
        $sheet2->getColumnDimension('E')->setWidth(25);
        $sheet2->getColumnDimension('F')->setWidth(25);
        $sheet2->getColumnDimension('G')->setWidth(25);
        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        });
        $filename = 'mouvements_stock_' . preg_replace('/\s+/', '_', strtolower($chantierNom)) . '_' . date('Ymd_His') . '.xlsx';
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;
    }
}