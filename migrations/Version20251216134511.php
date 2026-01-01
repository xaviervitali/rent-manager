<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251216134511 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE charge_type (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, is_recoverable TINYINT(1) NOT NULL, is_rent_component TINYINT(1) NOT NULL, periodicity VARCHAR(50) NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE housing (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, city_code VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, building VARCHAR(255) DEFAULT NULL, apartment_number VARCHAR(255) DEFAULT NULL, note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_FB8142C3A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE imputation (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(10, 2) NOT NULL, period_start DATE NOT NULL, period_end DATE DEFAULT NULL, note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, housing_id INT NOT NULL, type_id INT NOT NULL, INDEX IDX_AE81A25AAD5873E3 (housing_id), INDEX IDX_AE81A25AC54C8C93 (type_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE lease (id INT AUTO_INCREMENT NOT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, note LONGTEXT DEFAULT NULL, contract_file VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, housing_id INT NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_E6C77495AD5873E3 (housing_id), INDEX IDX_E6C77495A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE lease_tenant (id INT AUTO_INCREMENT NOT NULL, percentage INT NOT NULL, active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, lease_id INT NOT NULL, tenant_id INT NOT NULL, INDEX IDX_32081F82D3CA542C (lease_id), INDEX IDX_32081F829033212A (tenant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, amount NUMERIC(10, 2) NOT NULL, method VARCHAR(50) NOT NULL, reference VARCHAR(255) DEFAULT NULL, note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, lease_id INT NOT NULL, INDEX IDX_6D28840DD3CA542C (lease_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE quittance (id INT AUTO_INCREMENT NOT NULL, number VARCHAR(50) NOT NULL, amount NUMERIC(10, 2) NOT NULL, payment_date DATE NOT NULL, period_start DATE NOT NULL, period_end DATE NOT NULL, payment_method VARCHAR(100) DEFAULT NULL, reference VARCHAR(255) DEFAULT NULL, pdf_path VARCHAR(255) DEFAULT NULL, note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, imputation_id INT NOT NULL, UNIQUE INDEX UNIQ_D57587DD96901F54 (number), INDEX IDX_D57587DD1E40325 (imputation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE rent_receipt (id INT AUTO_INCREMENT NOT NULL, period_start DATE NOT NULL, period_end DATE NOT NULL, rent_amount NUMERIC(10, 2) NOT NULL, recoverable_charges NUMERIC(10, 2) NOT NULL, total_due NUMERIC(10, 2) NOT NULL, total_paid NUMERIC(10, 2) NOT NULL, payment_method VARCHAR(50) DEFAULT NULL, generated_at DATETIME NOT NULL, pdf_file VARCHAR(255) DEFAULT NULL, note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, lease_id INT NOT NULL, INDEX IDX_B2B5CD35D3CA542C (lease_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tenant (id INT AUTO_INCREMENT NOT NULL, firstname VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_4E59C462E7927C74 (email), INDEX IDX_4E59C462A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, firstname VARCHAR(255) DEFAULT NULL, lastname VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE housing ADD CONSTRAINT FK_FB8142C3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE imputation ADD CONSTRAINT FK_AE81A25AAD5873E3 FOREIGN KEY (housing_id) REFERENCES housing (id)');
        $this->addSql('ALTER TABLE imputation ADD CONSTRAINT FK_AE81A25AC54C8C93 FOREIGN KEY (type_id) REFERENCES charge_type (id)');
        $this->addSql('ALTER TABLE lease ADD CONSTRAINT FK_E6C77495AD5873E3 FOREIGN KEY (housing_id) REFERENCES housing (id)');
        $this->addSql('ALTER TABLE lease ADD CONSTRAINT FK_E6C77495A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE lease_tenant ADD CONSTRAINT FK_32081F82D3CA542C FOREIGN KEY (lease_id) REFERENCES lease (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lease_tenant ADD CONSTRAINT FK_32081F829033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DD3CA542C FOREIGN KEY (lease_id) REFERENCES lease (id)');
        $this->addSql('ALTER TABLE quittance ADD CONSTRAINT FK_D57587DD1E40325 FOREIGN KEY (imputation_id) REFERENCES imputation (id)');
        $this->addSql('ALTER TABLE rent_receipt ADD CONSTRAINT FK_B2B5CD35D3CA542C FOREIGN KEY (lease_id) REFERENCES lease (id)');
        $this->addSql('ALTER TABLE tenant ADD CONSTRAINT FK_4E59C462A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE housing DROP FOREIGN KEY FK_FB8142C3A76ED395');
        $this->addSql('ALTER TABLE imputation DROP FOREIGN KEY FK_AE81A25AAD5873E3');
        $this->addSql('ALTER TABLE imputation DROP FOREIGN KEY FK_AE81A25AC54C8C93');
        $this->addSql('ALTER TABLE lease DROP FOREIGN KEY FK_E6C77495AD5873E3');
        $this->addSql('ALTER TABLE lease DROP FOREIGN KEY FK_E6C77495A76ED395');
        $this->addSql('ALTER TABLE lease_tenant DROP FOREIGN KEY FK_32081F82D3CA542C');
        $this->addSql('ALTER TABLE lease_tenant DROP FOREIGN KEY FK_32081F829033212A');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DD3CA542C');
        $this->addSql('ALTER TABLE quittance DROP FOREIGN KEY FK_D57587DD1E40325');
        $this->addSql('ALTER TABLE rent_receipt DROP FOREIGN KEY FK_B2B5CD35D3CA542C');
        $this->addSql('ALTER TABLE tenant DROP FOREIGN KEY FK_4E59C462A76ED395');
        $this->addSql('DROP TABLE charge_type');
        $this->addSql('DROP TABLE housing');
        $this->addSql('DROP TABLE imputation');
        $this->addSql('DROP TABLE lease');
        $this->addSql('DROP TABLE lease_tenant');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE quittance');
        $this->addSql('DROP TABLE rent_receipt');
        $this->addSql('DROP TABLE tenant');
        $this->addSql('DROP TABLE user');
    }
}
