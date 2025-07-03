<?php

namespace App\Command;

use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-articles',
    description: 'Crée des articles de test pour la démonstration',
)]
class CreateTestArticlesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $articlesData = [
            [
                'nom' => 'Clou acier',
                'reference' => 'CL001',
                'marque' => 'Acme',
                'unite' => 'Boîte',
                'prix' => 5.00,
                'description' => 'Clou pour charpente en acier galvanisé',
                'numero' => 1001,
                'type' => 'Clou'
            ],
            [
                'nom' => 'Vis bois',
                'reference' => 'VS002', 
                'marque' => 'Fixit',
                'unite' => 'Sachet',
                'prix' => 3.50,
                'description' => 'Vis pour bois torx de 4x40mm',
                'numero' => 1002,
                'type' => 'Vis'
            ],
            [
                'nom' => 'Boulon M8',
                'reference' => 'BL003',
                'marque' => 'StrongBolt', 
                'unite' => 'Pièce',
                'prix' => 1.20,
                'description' => 'Boulon hexagonal M8x50 avec écrou',
                'numero' => 1003,
                'type' => 'Boulon'
            ],
            [
                'nom' => 'Perceuse électrique',
                'reference' => 'PE004',
                'marque' => 'Bosch',
                'unite' => 'Unité',
                'prix' => 89.99,
                'description' => 'Perceuse électrique 650W avec mandrin automatique',
                'numero' => 1004,
                'type' => 'Outillage'
            ],
            [
                'nom' => 'Ciment Portland',
                'reference' => 'CI005',
                'marque' => 'Lafarge',
                'unite' => 'Sac 25kg',
                'prix' => 12.50,
                'description' => 'Ciment Portland gris CEM I 52.5 N',
                'numero' => 1005,
                'type' => 'Matériau'
            ],
            [
                'nom' => 'Tuyau PVC',
                'reference' => 'TV006',
                'marque' => 'Nicoll',
                'unite' => 'Mètre',
                'prix' => 8.75,
                'description' => 'Tuyau PVC évacuation diamètre 100mm',
                'numero' => 1006,
                'type' => 'Plomberie'
            ]
        ];

        $count = 0;
        foreach ($articlesData as $data) {
            $article = new Article();
            $article->setNom($data['nom'])
                   ->setReference($data['reference'])
                   ->setMarque($data['marque'])
                   ->setUnite($data['unite'])
                   ->setPrix($data['prix'])
                   ->setDescription($data['description'])
                   ->setNumero($data['numero'])
                   ->setType($data['type']);

            $this->entityManager->persist($article);
            $count++;
        }

        $this->entityManager->flush();

        $io->success("$count articles de test créés avec succès !");
        $io->note('Vous pouvez maintenant voir ces articles sur la page /article');

        return Command::SUCCESS;
    }
}
