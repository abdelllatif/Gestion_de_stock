<?php

namespace App\Command;

use App\Entity\Chauffeur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-chauffeurs',
    description: 'Crée des chauffeurs de test pour la démonstration',
)]
class CreateTestChauffeursCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $chauffeursData = [
            [
                'nom' => 'El Amrani Mohammed',
                'licence' => 'LIC-12345-B',
                'tele' => '0661234567'
            ],
            [
                'nom' => 'Benjelloun Yassine',
                'licence' => 'LIC-67890-C',
                'tele' => '0662345678'
            ],
            [
                'nom' => 'Alaoui Hamid',
                'licence' => 'LIC-24680-A',
                'tele' => '0663456789'
            ],
            [
                'nom' => 'Tahiri Khalid',
                'licence' => 'LIC-13579-D',
                'tele' => '0664567890'
            ],
            [
                'nom' => 'Benali Rachid',
                'licence' => 'LIC-97531-E',
                'tele' => '0665678901'
            ],
        ];

        $count = 0;
        foreach ($chauffeursData as $data) {
            // Vérifier si le chauffeur existe déjà
            $existingChauffeur = $this->entityManager->getRepository(Chauffeur::class)
                ->findOneBy(['nom' => $data['nom']]);
            
            if ($existingChauffeur) {
                $io->note("Le chauffeur '{$data['nom']}' existe déjà. Mise à jour des informations.");
                $chauffeur = $existingChauffeur;
            } else {
                $chauffeur = new Chauffeur();
                $chauffeur->setNom($data['nom']);
                $count++;
            }

            // Configurer le chauffeur
            $chauffeur->setLicence($data['licence'])
                ->setTele($data['tele']);

            $this->entityManager->persist($chauffeur);
        }

        $this->entityManager->flush();

        if ($count > 0) {
            $io->success("$count nouveaux chauffeurs créés avec succès!");
        } else {
            $io->success("Les chauffeurs ont été mis à jour.");
        }

        return Command::SUCCESS;
    }
}
