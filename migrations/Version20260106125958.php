<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260106125958 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE housing_organization DROP FOREIGN KEY `FK_B9036DF932C8A3DE`');
        $this->addSql('ALTER TABLE housing_organization DROP FOREIGN KEY `FK_B9036DF9AD5873E3`');
        $this->addSql('DROP TABLE housing_organization');
        $this->addSql('ALTER TABLE lease DROP FOREIGN KEY `FK_E6C7749532C8A3DE`');
        $this->addSql('DROP INDEX IDX_E6C7749532C8A3DE ON lease');
        $this->addSql('ALTER TABLE lease DROP organization_id');
        $this->addSql('ALTER TABLE organization ADD description VARCHAR(255) DEFAULT NULL, ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE organization_member ADD invited_by VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE housing_organization (housing_id INT NOT NULL, organization_id INT NOT NULL, INDEX IDX_B9036DF932C8A3DE (organization_id), INDEX IDX_B9036DF9AD5873E3 (housing_id), PRIMARY KEY (housing_id, organization_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE housing_organization ADD CONSTRAINT `FK_B9036DF932C8A3DE` FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE housing_organization ADD CONSTRAINT `FK_B9036DF9AD5873E3` FOREIGN KEY (housing_id) REFERENCES housing (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lease ADD organization_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE lease ADD CONSTRAINT `FK_E6C7749532C8A3DE` FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('CREATE INDEX IDX_E6C7749532C8A3DE ON lease (organization_id)');
        $this->addSql('ALTER TABLE organization DROP description, DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE organization_member DROP invited_by');
    }
}
