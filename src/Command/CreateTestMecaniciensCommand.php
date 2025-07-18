<?php

namespace App\Command;

use App\Entity\Mecanicien;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

#[AsCommand(
    name: 'app:create-test-mecaniciens',
    description: 'Crée des mécaniciens de test pour la démonstration',
)]
class CreateTestMecaniciensCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $mecaniciensData = [
            [
                'nom' => 'Bouazza Hassan',
                'tele' => '0661122334'
            ],
            [
                'nom' => 'El Ouardi Karim',
                'tele' => '0662233445'
            ],
            [
                'nom' => 'Mansouri Jamal',
                'tele' => '0663344556'
            ],
            [
                'nom' => 'El Idrissi Samir',
                'tele' => '0664455667'
            ],
            [
                'nom' => 'El Fassi Mostafa',
                'tele' => '0665566778'
            ],
        ];

        $count = 0;
        foreach ($mecaniciensData as $data) {
            // Vérifier si le mécanicien existe déjà
            $existingMecanicien = $this->entityManager->getRepository(Mecanicien::class)
                ->findOneBy(['nom' => $data['nom']]);
            
            if ($existingMecanicien) {
                $io->note("Le mécanicien '{$data['nom']}' existe déjà. Mise à jour des informations.");
                $mecanicien = $existingMecanicien;
            } else {
                $mecanicien = new Mecanicien();
                $mecanicien->setNom($data['nom']);
                $count++;
            }

            // Configurer le mécanicien
            $mecanicien->setTele($data['tele']);

            $this->entityManager->persist($mecanicien);
        }

        $this->entityManager->flush();

        if ($count > 0) {
            $io->success("$count nouveaux mécaniciens créés avec succès!");
        } else {
            $io->success("Les mécaniciens ont été mis à jour.");
        }

        return Command::SUCCESS;
    }
}
