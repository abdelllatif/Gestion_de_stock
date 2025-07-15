<?php

namespace App\Command;

use App\Entity\Entretien;
use App\Entity\Vidange;
use App\Entity\Reparation;
use App\Entity\Machine;
use App\Entity\Chantier;
use App\Entity\Mecanicien;
use App\Entity\Chauffeur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-entretiens',
    description: 'Crée des entretiens, vidanges et réparations de test pour la démonstration',
)]
class CreateTestEntretiensCommand extends Command
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

        $mecaniciens = $this->entityManager->getRepository(Mecanicien::class)->findAll();
        if (empty($mecaniciens)) {
            $io->warning('Aucun mécanicien trouvé. Exécutez d\'abord la commande app:create-test-mecaniciens.');
            return Command::FAILURE;
        }

        $chauffeurs = $this->entityManager->getRepository(Chauffeur::class)->findAll();
        if (empty($chauffeurs)) {
            $io->warning('Aucun chauffeur trouvé. Exécutez d\'abord la commande app:create-test-chauffeurs.');
            return Command::FAILURE;
        }

        // Créer des entretiens avec vidanges et/ou réparations
        $entretienCount = 0;
        $vidangeCount = 0;
        $reparationCount = 0;

        // Créer 10 entretiens
        for ($i = 1; $i <= 10; $i++) {
            $entretien = new Entretien();
            $entretien->setNumero('ENT-2025-' . str_pad($i, 4, '0', STR_PAD_LEFT));
            $entretien->setDate(new \DateTime('-' . rand(1, 60) . ' days'));
            
            // Assigner une machine aléatoire
            $entretien->setMachine($machines[array_rand($machines)]);
            
            // Assigner un chantier aléatoire
            $entretien->setChantier($chantiers[array_rand($chantiers)]);
            
            // Assigner un mécanicien aléatoire
            $entretien->setMecanicien($mecaniciens[array_rand($mecaniciens)]);
            
            // Assigner un chauffeur aléatoire
            $entretien->setChauffeur($chauffeurs[array_rand($chauffeurs)]);
            
            $this->entityManager->persist($entretien);
            $entretienCount++;
            
            // 70% de chance d'avoir une vidange
            if (rand(1, 100) <= 70) {
                $vidange = new Vidange();
                $vidange->setDate($entretien->getDate());
                $vidange->setConsomation((string)rand(3, 10));
                $vidange->setTypeChangment('huile');
                $vidange->setConsoProchaineVidange((float)rand(5000, 10000));
                $vidange->setProchaineFilterChange((float)rand(5000, 10000));
                $vidange->setMontantTtc((float)rand(300, 1500));
                $vidange->setEntretien($entretien);
                
                // Kilomètres actuels
                $vidange->setKilometre(rand(5000, 50000));
                
                // Prochaine vidange en km
                $vidange->setProchaineVidange(rand(5000, 10000));
                
                // Filtres
                if (rand(1, 100) <= 60) {
                    $vidange->setKmFiltreHuile(rand(5000, 10000));
                }
                
                if (rand(1, 100) <= 40) {
                    $vidange->setKmFiltreGasoil(rand(5000, 10000));
                }
                
                if (rand(1, 100) <= 30) {
                    $vidange->setKmFiltreAir(rand(5000, 10000));
                }
                
                // Notes éventuelles
                if (rand(1, 100) <= 30) {
                    $vidange->setNotes('Vidange effectuée selon les recommandations du fabricant.');
                }
                
                $this->entityManager->persist($vidange);
                $vidangeCount++;
            }
            
            // 50% de chance d'avoir une réparation
            if (rand(1, 100) <= 50) {
                $reparation = new Reparation();
                $reparation->setDate($entretien->getDate());
                
                // Choisir aléatoirement une description parmi une liste
                $descriptions = [
                    'Remplacement des plaquettes de frein',
                    'Réparation du système électrique',
                    'Remplacement de la pompe à eau',
                    'Réparation du système d\'injection',
                    'Remplacement des amortisseurs',
                    'Réparation de la boîte de vitesses',
                    'Changement du joint de culasse'
                ];
                
                $reparation->setDesignations($descriptions[array_rand($descriptions)]);
                $reparation->setObservation('Nécessaire suite à une usure normale.');
                $reparation->setConsomation((float)rand(1, 5));
                $reparation->setMontantTtc((float)rand(500, 3000));
                $reparation->setEntretien($entretien);
                
                $this->entityManager->persist($reparation);
                $reparationCount++;
            }
        }
        
        $this->entityManager->flush();

        $io->success([
            "$entretienCount entretiens créés avec succès!",
            "$vidangeCount vidanges créées avec succès!",
            "$reparationCount réparations créées avec succès!"
        ]);

        return Command::SUCCESS;
    }
}
