<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260112141748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE charge_type CHANGE periodicity periodicity VARCHAR(50) NOT NULL, CHANGE direction direction VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE user ADD password_reset_code VARCHAR(6) DEFAULT NULL, ADD password_reset_code_expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE charge_type CHANGE periodicity periodicity VARCHAR(255) NOT NULL, CHANGE direction direction VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user DROP password_reset_code, DROP password_reset_code_expires_at');
    }
}
