<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208105658 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des fonctionnalités comptables LMNP : Asset (immobilisations), isTaxDeductible sur ChargeType, et amélioration de Credit';
    }

    public function up(Schema $schema): void
    {
        // Création de la table rm_asset pour les immobilisations amortissables
        $this->addSql('CREATE TABLE rm_asset (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, acquisition_value NUMERIC(12, 2) NOT NULL, acquisition_date DATE NOT NULL, depreciation_duration INT NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, housing_id INT NOT NULL, INDEX IDX_586373C2AD5873E3 (housing_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE rm_asset ADD CONSTRAINT FK_586373C2AD5873E3 FOREIGN KEY (housing_id) REFERENCES rm_housing (id)');

        // Ajout du champ isTaxDeductible sur ChargeType (déductible fiscalement) avec valeur par défaut
        $this->addSql('ALTER TABLE rm_charge_type ADD is_tax_deductible TINYINT(1) NOT NULL DEFAULT 0');

        // Amélioration de la table Credit
        $this->addSql('ALTER TABLE rm_credit ADD insurance_monthly NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE rm_credit ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE rm_credit ADD updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE rm_credit CHANGE amount amount NUMERIC(12, 2) NOT NULL');
        $this->addSql('ALTER TABLE rm_credit CHANGE rate rate NUMERIC(5, 3) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rm_asset DROP FOREIGN KEY FK_586373C2AD5873E3');
        $this->addSql('DROP TABLE rm_asset');
        $this->addSql('ALTER TABLE rm_charge_type DROP is_tax_deductible');
        $this->addSql('ALTER TABLE rm_credit DROP insurance_monthly, DROP created_at, DROP updated_at, CHANGE amount amount DOUBLE PRECISION NOT NULL, CHANGE rate rate DOUBLE PRECISION NOT NULL');
    }
}
