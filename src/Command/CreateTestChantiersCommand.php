<?php

namespace App\Command;

use App\Entity\Chantier;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-chantiers',
    description: 'Crée des chantiers de test pour la démonstration',
)]
class CreateTestChantiersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Récupérer des utilisateurs pour les affecter comme responsables
        $userRepository = $this->entityManager->getRepository(User::class);
        $users = $userRepository->findAll();
        
        if (empty($users)) {
            $io->warning('Aucun utilisateur trouvé. Exécutez d\'abord la commande app:create-test-users.');
            return Command::FAILURE;
        }

        $chantiersData = [
            [
                'nom' => 'Immeuble résidentiel Les Terrasses',
                'adresse' => '45 Avenue Mohammed V, Casablanca',
                'date_debut' => new \DateTime('2025-01-15'),
                'date_fin' => new \DateTime('2026-06-30'),
                'description' => 'Construction d\'un immeuble résidentiel de 8 étages avec 40 appartements'
            ],
            [
                'nom' => 'Centre commercial Marjane 2',
                'adresse' => 'Route de Rabat, Tanger',
                'date_debut' => new \DateTime('2025-03-01'),
                'date_fin' => new \DateTime('2026-12-15'),
                'description' => 'Extension du centre commercial avec 50 nouveaux magasins'
            ],
            [
                'nom' => 'Pont Hassan II',
                'adresse' => 'Oued Bouregreg, Rabat-Salé',
                'date_debut' => new \DateTime('2025-02-10'),
                'date_fin' => new \DateTime('2027-05-20'),
                'description' => 'Construction d\'un pont reliant Rabat et Salé'
            ],
            [
                'nom' => 'École Al Maarif',
                'adresse' => 'Quartier Al Maarif, Fès',
                'date_debut' => new \DateTime('2025-06-01'),
                'date_fin' => new \DateTime('2026-08-30'),
                'description' => 'Construction d\'une école primaire et secondaire'
            ],
            [
                'nom' => 'Autoroute Marrakech-Agadir',
                'adresse' => 'Tronçon 3, Marrakech',
                'date_debut' => new \DateTime('2025-04-15'),
                'date_fin' => new \DateTime('2028-10-31'),
                'description' => 'Extension de l\'autoroute reliant Marrakech à Agadir'
            ],
        ];

        $count = 0;
        foreach ($chantiersData as $data) {
            // Vérifier si le chantier existe déjà
            $existingChantier = $this->entityManager->getRepository(Chantier::class)
                ->findOneBy(['nom' => $data['nom']]);
            
            if ($existingChantier) {
                $io->note("Le chantier '{$data['nom']}' existe déjà. Mise à jour des informations.");
                $chantier = $existingChantier;
            } else {
                $chantier = new Chantier();
                $chantier->setNom($data['nom']);
                $count++;
            }

            // Assigner un responsable aléatoire parmi les utilisateurs
            $responsable = $users[array_rand($users)];
            
            // Configurer le chantier
            $chantier->setAddress($data['adresse'])
                ->setType('Construction')
                ->setTeleResponsable($responsable->getTele() ?? '0600000000')
                ->setResponsable($responsable->getPrenom() . ' ' . $responsable->getNom());
                
            // Ajouter quelques utilisateurs aléatoires au chantier
            // Note: getUsers() ne semble pas être défini dans l'entité Chantier, 
            // nous devons utiliser les méthodes appropriées
            $numUsers = rand(1, 3);
            $shuffledUsers = $users;
            shuffle($shuffledUsers);
            
            for ($i = 0; $i < $numUsers && $i < count($shuffledUsers); $i++) {
                if (method_exists($chantier, 'addUser')) {
                    $chantier->addUser($shuffledUsers[$i]);
                }
            }

            $this->entityManager->persist($chantier);
        }

        $this->entityManager->flush();

        if ($count > 0) {
            $io->success("$count nouveaux chantiers créés avec succès!");
        } else {
            $io->success("Les chantiers ont été mis à jour.");
        }

        return Command::SUCCESS;
    }
}
