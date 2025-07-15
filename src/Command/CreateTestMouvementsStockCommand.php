<?php

namespace App\Command;

use App\Entity\MouvementStock;
use App\Entity\Article;
use App\Entity\Chantier;
use App\Entity\Machine;
use App\Entity\Stock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-mouvements-stock',
    description: 'Crée des données de test pour les mouvements de stock',
)]
class CreateTestMouvementsStockCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private array $typesMouvement = [
        'entrée', 'sortie', 'transfert', 'retour'
    ];
    
    private array $fournisseurs = [
        'Fournisseur BTP Pro', 'MatériElite', 'Construction Supply', 'OutilPlus', 
        'BTP Solutions', 'ChantierExpress', 'MachinesMaster', 'MécaMatériel'
    ];
    
    private array $statuts = ['approved', 'waiting', 'rejected'];

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Création des données de test pour les mouvements de stock');

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

        // Récupérer les stocks
        $stocks = $this->entityManager->getRepository(Stock::class)->findAll();
        if (empty($stocks)) {
            $io->error('Aucun stock trouvé. Veuillez d\'abord exécuter la commande app:create-test-stocks.');
            return Command::FAILURE;
        }

        $mouvementsCount = 0;
        $startDate = new \DateTime('-6 months');
        $endDate = new \DateTime();

        // Créer des mouvements de stock
        for ($i = 0; $i < 50; $i++) {
            $mouvement = new MouvementStock();
            
            // Type de mouvement
            $type = $this->typesMouvement[array_rand($this->typesMouvement)];
            $mouvement->setType($type);
            
            // Date aléatoire entre -6 mois et aujourd'hui
            $date = clone $startDate;
            $date->add(new \DateInterval('P' . rand(0, 180) . 'D'));
            $mouvement->setDate($date);
            
            // Quantité
            $mouvement->setQuantite(rand(1, 50));
            
            // Observation
            $observations = [
                'Mouvement régulier', 'Réapprovisionnement urgent', 
                'Transfert planifié', 'Retour de matériel non utilisé',
                'Stock complémentaire', 'Remplacement de stock défectueux'
            ];
            $mouvement->setObservation($observations[array_rand($observations)]);
            
            // Statut
            $mouvement->setStatus($this->statuts[array_rand($this->statuts)]);

            // Affectation chantier et article/machine selon le type
            switch ($type) {
                case 'entrée':
                    // Entrée dans un chantier, avec un fournisseur
                    $mouvement->setChantierRec($chantiers[array_rand($chantiers)]);
                    $mouvement->setFournisseur($this->fournisseurs[array_rand($this->fournisseurs)]);
                    
                    // 80% chance d'être un article, 20% une machine
                    if (rand(1, 100) <= 80) {
                        $mouvement->setArticle($articles[array_rand($articles)]);
                        $mouvement->setBonEtat(rand(5, $mouvement->getQuantite()));
                        $mauvaisEtat = rand(0, $mouvement->getQuantite() - $mouvement->getBonEtat());
                        $mouvement->setMauvaisEtat($mauvaisEtat);
                        $mouvement->setFerailleEtat($mouvement->getQuantite() - $mouvement->getBonEtat() - $mauvaisEtat);
                    } elseif (!empty($machines)) {
                        $mouvement->setMachine($machines[array_rand($machines)]);
                        $mouvement->setBonEtat($mouvement->getQuantite());
                    }
                    break;
                    
                case 'sortie':
                    // Sortie d'un chantier
                    $mouvement->setChantierExp($chantiers[array_rand($chantiers)]);
                    
                    // 90% chance d'être un article, 10% une machine
                    if (rand(1, 100) <= 90) {
                        $mouvement->setArticle($articles[array_rand($articles)]);
                    } elseif (!empty($machines)) {
                        $mouvement->setMachine($machines[array_rand($machines)]);
                    }
                    break;
                    
                case 'transfert':
                    // Transfert entre deux chantiers
                    $chantierExp = $chantiers[array_rand($chantiers)];
                    $mouvement->setChantierExp($chantierExp);
                    
                    // S'assurer que le chantier de réception est différent
                    $chantiersRestants = array_filter($chantiers, function($c) use ($chantierExp) {
                        return $c->getId() !== $chantierExp->getId();
                    });
                    
                    if (!empty($chantiersRestants)) {
                        $mouvement->setChantierRec($chantiersRestants[array_rand($chantiersRestants)]);
                        
                        // 85% chance d'être un article, 15% une machine
                        if (rand(1, 100) <= 85) {
                            $mouvement->setArticle($articles[array_rand($articles)]);
                            $mouvement->setBonEtat(rand(5, $mouvement->getQuantite()));
                            $mauvaisEtat = rand(0, $mouvement->getQuantite() - $mouvement->getBonEtat());
                            $mouvement->setMauvaisEtat($mauvaisEtat);
                            $mouvement->setFerailleEtat($mouvement->getQuantite() - $mouvement->getBonEtat() - $mauvaisEtat);
                        } elseif (!empty($machines)) {
                            $mouvement->setMachine($machines[array_rand($machines)]);
                            $mouvement->setBonEtat(rand(1, $mouvement->getQuantite()));
                            $mouvement->setMauvaisEtat(rand(0, $mouvement->getQuantite() - $mouvement->getBonEtat()));
                        }
                    } else {
                        // Si un seul chantier, on change le type en entrée
                        $mouvement->setType('entrée');
                        $mouvement->setChantierRec($chantierExp);
                        $mouvement->setChantierExp(null);
                        $mouvement->setFournisseur($this->fournisseurs[array_rand($this->fournisseurs)]);
                        $mouvement->setArticle($articles[array_rand($articles)]);
                    }
                    break;
                    
                case 'retour':
                    // Retour vers un fournisseur
                    $mouvement->setChantierExp($chantiers[array_rand($chantiers)]);
                    $mouvement->setFournisseur($this->fournisseurs[array_rand($this->fournisseurs)]);
                    
                    // 70% chance d'être un article, 30% une machine
                    if (rand(1, 100) <= 70) {
                        $mouvement->setArticle($articles[array_rand($articles)]);
                        // Pour un retour, généralement plus de stock en mauvais état
                        $mouvement->setBonEtat(rand(0, $mouvement->getQuantite() / 2));
                        $mouvement->setMauvaisEtat(rand($mouvement->getQuantite() / 2, $mouvement->getQuantite()));
                    } elseif (!empty($machines)) {
                        $mouvement->setMachine($machines[array_rand($machines)]);
                        $mouvement->setMauvaisEtat($mouvement->getQuantite());
                    }
                    break;
            }
            
            $this->entityManager->persist($mouvement);
            $mouvementsCount++;
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d mouvements de stock ont été créés avec succès', $mouvementsCount));
        return Command::SUCCESS;
    }
}
