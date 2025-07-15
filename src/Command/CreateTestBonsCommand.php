<?php

namespace App\Command;

use App\Entity\Bon;
use App\Entity\BonDetails;
use App\Entity\Article;
use App\Entity\Machine;
use App\Entity\Chantier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-bons',
    description: 'Crée des bons de livraison et sortie de test pour la démonstration',
)]
class CreateTestBonsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Vérifier si les entités nécessaires existent
        $articles = $this->entityManager->getRepository(Article::class)->findAll();
        if (empty($articles)) {
            $io->warning('Aucun article trouvé. Exécutez d\'abord la commande app:create-test-articles.');
            return Command::FAILURE;
        }

        $machines = $this->entityManager->getRepository(Machine::class)->findAll();
        if (empty($machines)) {
            $io->warning('Aucune machine trouvée. Exécutez d\'abord la commande app:create-test-machines.');
            return Command::FAILURE;
        }

        $chantiers = $this->entityManager->getRepository(Chantier::class)->findAll();
        if (empty($chantiers)) {
            $io->warning('Aucun chantier trouvé. Exécutez d\'abord la commande app:create-test-chantiers.');
            return Command::FAILURE;
        }

        $bonCount = 0;
        $detailCount = 0;
        $fournisseurs = ['STOKVIS', 'DELTA BÉTON', 'MAROC MATÉRIAUX', 'BIMAGRO', 'FORGESUD'];

        // Créer 10 bons (5 livraisons, 5 sorties)
        for ($i = 1; $i <= 10; $i++) {
            $type = ($i <= 5) ? 'Livraison' : 'Sortie';
            
            $bon = new Bon();
            $bon->setType($type);
            $bon->setNumeroSerie($type === 'Livraison' ? 'BL-2025-' . str_pad($i, 4, '0', STR_PAD_LEFT) : 'BS-2025-' . str_pad($i, 4, '0', STR_PAD_LEFT));
            $bon->setDate(new \DateTime('-' . rand(1, 90) . ' days'));
            $bon->setNote($type === 'Livraison' ? 'Livraison de matériels pour le chantier' : 'Sortie de matériels du stock');
            
            // Assigner un chantier aléatoire
            $bon->setChantier($chantiers[array_rand($chantiers)]);
            
            $this->entityManager->persist($bon);
            $bonCount++;
            
            // Ajouter entre 2 et 5 détails pour chaque bon
            $numDetails = rand(2, 5);
            $itemsUsed = [];
            
            for ($j = 0; $j < $numDetails; $j++) {
                $detail = new BonDetails();
                
                // Décider si c'est un article ou une machine (80% articles, 20% machines)
                if (rand(1, 100) <= 80) {
                    // Choisir un article non déjà utilisé
                    $availableArticles = array_filter($articles, function($article) use ($itemsUsed) {
                        return !isset($itemsUsed['article_' . $article->getId()]);
                    });
                    
                    if (empty($availableArticles)) {
                        // Si tous les articles ont été utilisés, en prendre un au hasard
                        $article = $articles[array_rand($articles)];
                    } else {
                        $article = $availableArticles[array_rand($availableArticles)];
                    }
                    
                    $itemsUsed['article_' . $article->getId()] = true;
                    
                    $detail->setArticle($article);
                    $detail->setUnite($article->getUnite());
                } else {
                    // Choisir une machine non déjà utilisée
                    $availableMachines = array_filter($machines, function($machine) use ($itemsUsed) {
                        return !isset($itemsUsed['machine_' . $machine->getId()]);
                    });
                    
                    if (empty($availableMachines)) {
                        // Si toutes les machines ont été utilisées, en prendre une au hasard
                        $machine = $machines[array_rand($machines)];
                    } else {
                        $machine = $availableMachines[array_rand($availableMachines)];
                    }
                    
                    $itemsUsed['machine_' . $machine->getId()] = true;
                    
                    $detail->setMachine($machine);
                    $detail->setUnite('Unité');
                }
                
                $detail->setQuantite(rand(1, 10));
                
                if ($type === 'Livraison') {
                    $detail->setFournisseur($fournisseurs[array_rand($fournisseurs)]);
                } else {
                    $detail->setFournisseur('N/A');
                }
                
                $detail->setBon($bon);
                $this->entityManager->persist($detail);
                $detailCount++;
            }
        }

        $this->entityManager->flush();

        $io->success([
            "$bonCount bons créés avec succès!",
            "$detailCount détails de bon créés avec succès!"
        ]);

        return Command::SUCCESS;
    }
}
