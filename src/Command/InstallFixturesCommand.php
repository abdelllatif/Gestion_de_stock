<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:install-fixtures',
    description: 'Installe toutes les données de test en une seule commande',
)]
class InstallFixturesCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setDescription('Installe toutes les données de test en une seule commande');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Installation des données de test');

        $commands = [
            'app:create-test-users',
            'app:create-test-chantiers',
            'app:create-test-articles',
            'app:create-test-machines',
            'app:create-test-stocks',
            'app:create-test-mouvements-stock',
            'app:create-test-demandes-achat'
        ];

        $application = $this->getApplication();
        
        if (!$application) {
            $io->error('Erreur: Impossible d\'accéder à l\'application Symfony');
            return Command::FAILURE;
        }

        foreach ($commands as $commandName) {
            try {
                $command = $application->find($commandName);
                $commandInput = new ArrayInput([]);
                $commandInput->setInteractive(false);
                
                $io->section("Exécution de la commande: $commandName");
                $returnCode = $command->run($commandInput, $output);
                
                if ($returnCode !== Command::SUCCESS) {
                    $io->warning("La commande $commandName s'est terminée avec un code de retour non-zéro: $returnCode");
                }
                
                $io->newLine();
            } catch (\Exception $e) {
                $io->error("Erreur lors de l'exécution de la commande $commandName: " . $e->getMessage());
            }
        }

        $io->success('Toutes les données de test ont été installées avec succès!');
        $io->note([
            'Résumé des données créées:',
            '- Utilisateurs: admin, manager, user, tech',
            '- Chantiers: Différents sites de construction',
            '- Articles: Matériaux et fournitures de construction',
            '- Machines: Engins de chantier et véhicules',
            '- Stocks: Inventaire des articles et machines par chantier',
            '- Mouvements de stock: Entrées, sorties et transferts',
            '- Demandes d\'achat: Demandes de matériels et fournitures'
        ]);

        $io->section('Comment utiliser l\'application');
        $io->listing([
            'Connectez-vous avec l\'utilisateur "admin" et le mot de passe "admin123"',
            'Explorez les différentes sections du système',
            'Gérez les stocks, les mouvements et les demandes d\'achat'
        ]);

        return Command::SUCCESS;
    }
}
