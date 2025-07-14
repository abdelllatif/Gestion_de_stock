<?php

namespace App\Controller;

use App\Entity\MouvementStock;
use App\Entity\Stock;
use App\Repository\ArticleRepository;
use App\Repository\MachineRepository;
use App\Repository\ChantierRepository;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class StockController extends AbstractController
{
    #[Route('/stock', name: 'stock_index', methods: ['GET'])]
    public function index(ChantierRepository $chantierRepository, StockRepository $stockRepository, Request $request): Response
    {
        $session = $request->getSession();
        $selectedChantier = $session->get('selected_chantier');
        $chantierId = $selectedChantier['id'] ?? null;
        $chantierNom = $selectedChantier['nom'] ?? null;

        if (!$chantierId) {
            // Aucun chantier sélectionné
            return $this->render('stock/index.html.twig', [
                'chantier' => null,
                'stocks' => [],
                'chantier_nom' => null,
                'activeLink' => 'stock',
                'noChantierSelected' => true,
            ]);
        }

        // Récupérer le chantier sélectionné
        $chantier = $chantierRepository->find($chantierId);
        if (!$chantier) {
            $session->remove('selected_chantier');
            return $this->render('stock/index.html.twig', [
                'chantier' => null,
                'stocks' => [],
                'chantier_nom' => null,
                'activeLink' => 'stock',
                'noChantierSelected' => true,
            ]);
        }

        // Récupérer les stocks du chantier sélectionné
        $stocks = $stockRepository->findBy(['chantier' => $chantier]);

        return $this->render('stock/index.html.twig', [
            'chantier' => $chantier,
            'stocks' => $stocks,
            'chantier_nom' => $chantierNom,
            'activeLink' => 'stock',
            'noChantierSelected' => false,
        ]);
    }

    #[Route('/stock/ajouter-quantite', name: 'stock_ajouter_quantite', methods: ['POST'])]
    public function ajouterQuantite(
        Request $request,
        StockRepository $stockRepository,
        ArticleRepository $articleRepository,
        MachineRepository $machineRepository,
        ChantierRepository $chantierRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $id = $request->request->get('id');
        $type = $request->request->get('type');
        $quantite = (int) $request->request->get('quantite');
        $fournisseur = $request->request->get('fournisseur');
        $chantierNom = $request->request->get('chantier_nom');

        if (!$id || !$type || !$quantite || !$fournisseur || !$chantierNom) {
            return new JsonResponse(['message' => 'Paramètres manquants.'], 400);
        }

        // Find chantier by name
        $chantier = $chantierRepository->findOneBy(['nom' => $chantierNom]);
        if (!$chantier) {
            return new JsonResponse(['message' => 'Chantier introuvable.'], 404);
        }

        // Find stock by article or machine and chantier
        if ($type === 'article') {
            $article = $articleRepository->find($id);
            if (!$article) {
                return new JsonResponse(['message' => 'Article introuvable.'], 404);
            }
            $stock = $stockRepository->findOneBy(['article' => $article, 'chantier' => $chantier]);
            if (!$stock) {
                // Create new stock if not exists
                $stock = new Stock();
                $stock->setArticle($article);
                $stock->setChantier($chantier);
                $stock->setQuantiteChantier(0);
                $stock->setBonEtat(0);
                $stock->setMauvaisEtat(0);
                $stock->setFerailleEtat(0);
                $em->persist($stock);
            }
            $stock->setQuantiteChantier($stock->getQuantiteChantier() + $quantite);
            $stock->setBonEtat($stock->getBonEtat() + $quantite);
        } elseif ($type === 'machine') {
            $machine = $machineRepository->find($id);
            if (!$machine) {
                return new JsonResponse(['message' => 'Machine introuvable.'], 404);
            }
            $stock = $stockRepository->findOneBy(['machine' => $machine, 'chantier' => $chantier]);
            if (!$stock) {
                // Create new stock if not exists
                $stock = new Stock();
                $stock->setMachine($machine);
                $stock->setChantier($chantier);
                $stock->setQuantiteChantier(0);
                $stock->setBonEtat(0);
                $stock->setMauvaisEtat(0);
                $stock->setFerailleEtat(0);
                $em->persist($stock);
            }
            $stock->setQuantiteChantier($stock->getQuantiteChantier() + $quantite);
            // For machines, do not update status fields
        } else {
            return new JsonResponse(['message' => 'Type invalide.'], 400);
        }

        // Create MouvementStock
        $mouvement = new MouvementStock();
        $mouvement->setQuantite($quantite);
        $mouvement->setFournisseur($fournisseur);
        $mouvement->setType('Entrée');
        $mouvement->setDate(new \DateTime());
        $mouvement->setChantierRec($chantier);
        $mouvement->setObservation('Ajout de quantité via interface stock');
        if ($type === 'article') {
            $mouvement->setArticle($article);
        } elseif ($type === 'machine') {
            $mouvement->setMachine($machine);
        }
        $em->persist($mouvement);
        $em->flush();

        return new JsonResponse(['message' => 'Quantité ajoutée et mouvement créé.']);
    }
}
