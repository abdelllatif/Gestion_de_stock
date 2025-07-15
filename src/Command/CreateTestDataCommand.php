<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-data',
    description: 'Exécute toutes les commandes de création de données de test',
)]
class CreateTestDataCommand extends Command
{
    private array $commands = [
        'app:create-test-users',
        'app:create-test-articles',
        'app:create-test-machines',
        'app:create-test-chantiers',
        'app:create-test-chauffeurs',
        'app:create-test-mecaniciens',
        'app:create-test-entretiens',
        'app:create-test-bons',
        'app:create-test-stocks',
        'app:create-test-mouvements',
        'app:create-test-demandes',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $application = $this->getApplication();

        $io->title('Création des données de test pour le système de gestion de stock');

        foreach ($this->commands as $commandName) {
            $io->section("Exécution de la commande: $commandName");
            
            $command = $application->find($commandName);
            $returnCode = $command->run($input, $output);
            
            if ($returnCode !== Command::SUCCESS) {
                $io->error("La commande $commandName a échoué avec le code $returnCode");
                return Command::FAILURE;
            }
            
            $io->newLine();
        }

        $io->success('Toutes les données de test ont été créées avec succès!');

        return Command::SUCCESS;
    }
}
