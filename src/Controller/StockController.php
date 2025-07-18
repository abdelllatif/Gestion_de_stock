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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    #[Route('/stock/export-excel', name: 'stock_export_excel', methods: ['GET'])]
    public function exportExcel(Request $request, StockRepository $stockRepository, ChantierRepository $chantierRepository): Response
    {
        $session = $request->getSession();
        $selectedChantier = $session->get('selected_chantier');
        $chantierId = $selectedChantier['id'] ?? null;
        $chantierNom = $selectedChantier['nom'] ?? 'Chantier';

        if (!$chantierId) {
            $this->addFlash('error', 'Aucun chantier sélectionné.');
            return $this->redirectToRoute('stock_index');
        }
        $chantier = $chantierRepository->find($chantierId);
        if (!$chantier) {
            $this->addFlash('error', 'Chantier introuvable.');
            return $this->redirectToRoute('stock_index');
        }
        $stocks = $stockRepository->findBy(['chantier' => $chantier]);

        // Group stocks by category name
        $groupedStocks = [];
        foreach ($stocks as $stock) {
            $quantite = $stock->getQuantiteChantier();
            if ($quantite <= 0) continue;
            $categorie = '';
            if ($stock->getArticle() && $stock->getArticle()->getCategory()) {
                $categorie = $stock->getArticle()->getCategory()->getNom();
            } elseif ($stock->getMachine() && $stock->getMachine()->getCategorie()) {
                $categorie = $stock->getMachine()->getCategorie()->getNom();
            } else {
                $categorie = 'Sans catégorie';
            }
            $groupedStocks[$categorie][] = $stock;
        }
        ksort($groupedStocks);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Title row (merged, bold, large, colored)
        $title = 'Stock du Chantier ' . $chantierNom;
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1e3a8a');
        $sheet->getStyle('A1')->getFont()->getColor()->setRGB('FFFFFF');
        $row = 2;

        // Header row
        $headers = ['Nom', 'Type', 'Catégorie', 'Quantité', 'Bon', 'Mauvais', 'Ferraille'];
        $sheet->fromArray($headers, null, 'A' . $row);
        $sheet->getStyle('A'.$row.':G'.$row)->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A'.$row.':G'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A'.$row.':G'.$row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('3b82f6');
        $sheet->getStyle('A'.$row.':G'.$row)->getFont()->getColor()->setRGB('FFFFFF');
        $row++;

        // Table border style
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FF888888'],
                ],
            ],
        ];
        $mainBg = 'FFFFFF'; // pure white for info cells
        $catBg = 'e5e7eb';  // gray for category header

        foreach ($groupedStocks as $catName => $stocksInCat) {
            // Category header row
            $sheet->mergeCells('A'.$row.':G'.$row);
            $sheet->setCellValue('A'.$row, $catName);
            $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A'.$row.':G'.$row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($catBg);
            $sheet->getStyle('A'.$row.':G'.$row)->applyFromArray($borderStyle);
            $row++;
            foreach ($stocksInCat as $stock) {
                $nom = $stock->getArticle() ? $stock->getArticle()->getNom() : ($stock->getMachine() ? $stock->getMachine()->getNom() : '');
                $type = $stock->getArticle() ? 'Article' : ($stock->getMachine() ? 'Machine' : '');
                $categorie = $catName;
                $quantite = $stock->getQuantiteChantier();
                if ($type === 'Article') {
                    $bon = ($stock->getBonEtat() !== null && $stock->getBonEtat() !== '') ? $stock->getBonEtat() : 0;
                    $mauvais = ($stock->getMauvaisEtat() !== null && $stock->getMauvaisEtat() !== '') ? $stock->getMauvaisEtat() : 0;
                    $ferraille = ($stock->getFerailleEtat() !== null && $stock->getFerailleEtat() !== '') ? $stock->getFerailleEtat() : 0;
                } else {
                    $bon = $mauvais = $ferraille = '-';
                }
                $sheet->fromArray([
                    $nom, $type, $categorie ?: '-', $quantite, $bon, $mauvais, $ferraille
                ], null, 'A' . $row);
                $sheet->getStyle('A'.$row.':D'.$row)->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A'.$row.':D'.$row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($mainBg);
                $sheet->getStyle('A'.$row.':D'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                // Status columns: white background
                $sheet->getStyle('E'.$row.':G'.$row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
                $sheet->getStyle('E'.$row.':G'.$row)->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('E'.$row.':G'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A'.$row.':G'.$row)->applyFromArray($borderStyle);
                $row++;
            }
        }

        // Set even wider column widths
        $sheet->getColumnDimension('A')->setWidth(55);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(45);
        $sheet->getColumnDimension('D')->setWidth(28);
        $sheet->getColumnDimension('E')->setWidth(28);
        $sheet->getColumnDimension('F')->setWidth(28);
        $sheet->getColumnDimension('G')->setWidth(28);

        // Set row heights and font size for all table content (except title/header)
        $firstDataRow = 4; // Title is 1, header is 2, first category is 3, first data is 4+
        for ($i = 1; $i <= $sheet->getHighestRow(); $i++) {
            if ($i == 1) continue; // Title row already styled
            $sheet->getRowDimension($i)->setRowHeight(32);
            if ($i > 2) { // Data and category rows
                $sheet->getStyle('A'.$i.':G'.$i)->getFont()->setSize(15);
            }
            // Center align all content (horizontal and vertical)
            $sheet->getStyle('A'.$i.':G'.$i)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A'.$i.':G'.$i)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        }

        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        });
        $filename = 'stock_' . preg_replace('/\s+/', '_', strtolower($chantierNom)) . '_' . date('Ymd_His') . '.xlsx';
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;
    }
}
