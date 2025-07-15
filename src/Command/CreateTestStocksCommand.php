<?php

namespace App\Command;

use App\Entity\Stock;
use App\Entity\Article;
use App\Entity\Chantier;
use App\Entity\Machine;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-stocks',
    description: 'Crée des données de test pour les stocks',
)]
class CreateTestStocksCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Création des données de test pour les stocks');

        // Récupérer les articles
        $articles = $this->entityManager->getRepository(Article::class)->findAll();
        if (empty($articles)) {
            $io->error('Aucun article trouvé. Veuillez d\'abord exécuter la commande app:create-test-articles.');
            return Command::FAILURE;
        }

        // Récupérer les chantiers
        $chantiers = $this->entityManager->getRepository(Chantier::class)->findAll();
        if (empty($chantiers)) {
            $io->error('Aucun chantier trouvé. Veuillez d\'abord créer des chantiers.');
            return Command::FAILURE;
        }

        // Récupérer les machines
        $machines = $this->entityManager->getRepository(Machine::class)->findAll();

        $stocksCount = 0;

        // Créer des stocks pour les articles dans différents chantiers
        foreach ($chantiers as $chantier) {
            // Pour chaque chantier, attribuer quelques articles aléatoires
            $articlesForChantier = array_slice($articles, 0, rand(5, 15));
            foreach ($articlesForChantier as $article) {
                $stock = new Stock();
                $stock->setChantier($chantier);
                $stock->setArticle($article);
                $stock->setQuantiteChantier(rand(10, 100));
                $stock->setBonEtat(rand(8, 95));
                $stock->setMauvaisEtat(rand(0, 10));
                $stock->setFerailleEtat(rand(0, 5));

                $this->entityManager->persist($stock);
                $stocksCount++;
            }

            // Pour chaque chantier, attribuer quelques machines aléatoires si elles existent
            if (!empty($machines)) {
                $machinesForChantier = array_slice($machines, 0, rand(2, 5));
                foreach ($machinesForChantier as $machine) {
                    $stock = new Stock();
                    $stock->setChantier($chantier);
                    $stock->setMachine($machine);
                    $stock->setQuantiteChantier(rand(1, 5));
                    $stock->setBonEtat(rand(1, 5));
                    $stock->setMauvaisEtat(rand(0, 2));
                    $stock->setFerailleEtat(rand(0, 1));

                    $this->entityManager->persist($stock);
                    $stocksCount++;
                }
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d stocks ont été créés avec succès', $stocksCount));
        return Command::SUCCESS;
    }
}
