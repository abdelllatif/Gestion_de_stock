<?php

namespace App\Command;

use App\Entity\Machine;
use App\Entity\MachineCategorie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-machines',
    description: 'Crée des machines de test pour la démonstration',
)]
class CreateTestMachinesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Vérifier s'il existe au moins une catégorie
        $categorie = $this->entityManager->getRepository(MachineCategorie::class)->findOneBy([]);
        
        if (!$categorie) {
            // Créer une catégorie par défaut si aucune n'existe
            $categorie = new MachineCategorie();
            $categorie->setNom('Engins de chantier');
            $categorie->setDescription('Catégorie pour les engins de chantier');
            $this->entityManager->persist($categorie);
            
            $categorie2 = new MachineCategorie();
            $categorie2->setNom('Véhicules');
            $categorie2->setDescription('Catégorie pour les véhicules utilitaires');
            $this->entityManager->persist($categorie2);
            
            $this->entityManager->flush();
            
            $io->success('Catégories de machines créées avec succès');
        }

        $categorieRepository = $this->entityManager->getRepository(MachineCategorie::class);
        $categories = $categorieRepository->findAll();
        
        $machinesData = [
            [
                'nom' => 'Pelleteuse Caterpillar',
                'code' => 'PEL001',
                'nbr' => 'P-2025-01',
                'marque' => 'Caterpillar',
                'modele' => '320 GC',
                'anneeFabriq' => '2023',
                'categorie' => $categories[0] ?? $categorie
            ],
            [
                'nom' => 'Tractopelle JCB',
                'code' => 'TRA002',
                'nbr' => 'T-2025-02',
                'marque' => 'JCB',
                'modele' => '3CX',
                'anneeFabriq' => '2024',
                'categorie' => $categories[0] ?? $categorie
            ],
            [
                'nom' => 'Camion benne',
                'code' => 'CAM003',
                'nbr' => 'C-2025-03',
                'marque' => 'Volvo',
                'modele' => 'FH16',
                'anneeFabriq' => '2022',
                'categorie' => $categories[1] ?? $categorie
            ],
            [
                'nom' => 'Chargeuse sur pneus',
                'code' => 'CHA004',
                'nbr' => 'L-2025-04',
                'marque' => 'Komatsu',
                'modele' => 'WA320',
                'anneeFabriq' => '2023',
                'categorie' => $categories[0] ?? $categorie
            ],
            [
                'nom' => 'Grue mobile',
                'code' => 'GRU005',
                'nbr' => 'G-2025-05',
                'marque' => 'Liebherr',
                'modele' => 'LTM 1100',
                'anneeFabriq' => '2024',
                'categorie' => $categories[0] ?? $categorie
            ],
            [
                'nom' => 'Camionnette',
                'code' => 'CAM006',
                'nbr' => 'U-2025-06',
                'marque' => 'Mercedes',
                'modele' => 'Sprinter',
                'anneeFabriq' => '2025',
                'categorie' => $categories[1] ?? $categorie
            ]
        ];

        $count = 0;
        foreach ($machinesData as $data) {
            $machine = new Machine();
            $machine->setNom($data['nom'])
                   ->setCode($data['code'])
                   ->setNbr($data['nbr'])
                   ->setMarque($data['marque'])
                   ->setModele($data['modele'])
                   ->setAnneeFabriq($data['anneeFabriq'])
                   ->setCategorie($data['categorie']);

            $this->entityManager->persist($machine);
            $count++;
        }

        $this->entityManager->flush();

        $io->success("$count machines de test créées avec succès !");
        $io->note('Vous pouvez maintenant voir ces machines sur la page /machine');

        return Command::SUCCESS;
    }
}
