<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Suppression des champs type_huile et quantite de la table Vidange car ils sont redondants
 */
final class Version20250702000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Suppression des champs type_huile et quantite de la table Vidange';
    }

    public function up(Schema $schema): void
    {
        // Nous supprimons les colonnes type_huile et quantite de la table vidange
        $this->addSql('ALTER TABLE vidange DROP COLUMN IF EXISTS type_huile');
        $this->addSql('ALTER TABLE vidange DROP COLUMN IF EXISTS quantite');
    }

    public function down(Schema $schema): void
    {
        // Nous recréons les colonnes supprimées
        $this->addSql('ALTER TABLE vidange ADD type_huile VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE vidange ADD quantite DOUBLE PRECISION DEFAULT NULL');
    }
}
