<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260116110733 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE charge_type (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, is_recoverable TINYINT(1) NOT NULL, is_rent_component TINYINT(1) NOT NULL, periodicity VARCHAR(50) NOT NULL, direction VARCHAR(20) NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_C41D3AF6A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE housing (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, city_code VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, building VARCHAR(255) DEFAULT NULL, apartment_number VARCHAR(255) DEFAULT NULL, note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, type VARCHAR(255) DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_FB8142C3A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE imputation (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(10, 2) NOT NULL, period_start DATE NOT NULL, period_end DATE DEFAULT NULL, note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, housing_id INT NOT NULL, type_id INT NOT NULL, INDEX IDX_AE81A25AAD5873E3 (housing_id), INDEX IDX_AE81A25AC54C8C93 (type_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE lease (id INT AUTO_INCREMENT NOT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, note LONGTEXT DEFAULT NULL, contract_file VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, housing_id INT NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_E6C77495AD5873E3 (housing_id), INDEX IDX_E6C77495A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE lease_tenant (id INT AUTO_INCREMENT NOT NULL, percentage INT NOT NULL, active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, lease_id INT NOT NULL, tenant_id INT NOT NULL, INDEX IDX_32081F82D3CA542C (lease_id), INDEX IDX_32081F829033212A (tenant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE organization (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, city_code VARCHAR(20) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, note LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE organization_member (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(50) NOT NULL, joined_at DATETIME NOT NULL, invited_by VARCHAR(255) DEFAULT NULL, user_id INT NOT NULL, organization_id INT NOT NULL, INDEX IDX_756A2A8DA76ED395 (user_id), INDEX IDX_756A2A8D32C8A3DE (organization_id), UNIQUE INDEX unique_user_organization (user_id, organization_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE rent_receipt (id INT AUTO_INCREMENT NOT NULL, period_start DATE NOT NULL, period_end DATE NOT NULL, rent_amount NUMERIC(10, 2) NOT NULL, recoverable_charges NUMERIC(10, 2) NOT NULL, total_due NUMERIC(10, 2) NOT NULL, total_paid NUMERIC(10, 2) NOT NULL, payment_method VARCHAR(50) DEFAULT NULL, generated_at DATETIME NOT NULL, pdf_file VARCHAR(255) DEFAULT NULL, note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, lease_id INT NOT NULL, INDEX IDX_B2B5CD35D3CA542C (lease_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tenant (id INT AUTO_INCREMENT NOT NULL, firstname VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT DEFAULT NULL, organization_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_4E59C462E7927C74 (email), INDEX IDX_4E59C462A76ED395 (user_id), INDEX IDX_4E59C46232C8A3DE (organization_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, email_verified TINYINT(1) NOT NULL, email_verification_token VARCHAR(255) DEFAULT NULL, email_verification_token_expires_at DATETIME DEFAULT NULL, password_reset_code VARCHAR(6) DEFAULT NULL, password_reset_code_expires_at DATETIME DEFAULT NULL, organization_id INT DEFAULT NULL, INDEX IDX_8D93D64932C8A3DE (organization_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE charge_type ADD CONSTRAINT FK_C41D3AF6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE housing ADD CONSTRAINT FK_FB8142C3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE imputation ADD CONSTRAINT FK_AE81A25AAD5873E3 FOREIGN KEY (housing_id) REFERENCES housing (id)');
        $this->addSql('ALTER TABLE imputation ADD CONSTRAINT FK_AE81A25AC54C8C93 FOREIGN KEY (type_id) REFERENCES charge_type (id)');
        $this->addSql('ALTER TABLE lease ADD CONSTRAINT FK_E6C77495AD5873E3 FOREIGN KEY (housing_id) REFERENCES housing (id)');
        $this->addSql('ALTER TABLE lease ADD CONSTRAINT FK_E6C77495A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE lease_tenant ADD CONSTRAINT FK_32081F82D3CA542C FOREIGN KEY (lease_id) REFERENCES lease (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lease_tenant ADD CONSTRAINT FK_32081F829033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE organization_member ADD CONSTRAINT FK_756A2A8DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE organization_member ADD CONSTRAINT FK_756A2A8D32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE rent_receipt ADD CONSTRAINT FK_B2B5CD35D3CA542C FOREIGN KEY (lease_id) REFERENCES lease (id)');
        $this->addSql('ALTER TABLE tenant ADD CONSTRAINT FK_4E59C462A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tenant ADD CONSTRAINT FK_4E59C46232C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64932C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE rm_charge_type DROP FOREIGN KEY `FK_C41D3AF6A76ED395`');
        $this->addSql('ALTER TABLE rm_housing DROP FOREIGN KEY `FK_FB8142C3A76ED395`');
        $this->addSql('ALTER TABLE rm_imputation DROP FOREIGN KEY `FK_AE81A25AAD5873E3`');
        $this->addSql('ALTER TABLE rm_imputation DROP FOREIGN KEY `FK_AE81A25AC54C8C93`');
        $this->addSql('ALTER TABLE rm_lease DROP FOREIGN KEY `FK_E6C77495A76ED395`');
        $this->addSql('ALTER TABLE rm_lease DROP FOREIGN KEY `FK_E6C77495AD5873E3`');
        $this->addSql('ALTER TABLE rm_lease_tenant DROP FOREIGN KEY `FK_32081F829033212A`');
        $this->addSql('ALTER TABLE rm_lease_tenant DROP FOREIGN KEY `FK_32081F82D3CA542C`');
        $this->addSql('ALTER TABLE rm_organization_member DROP FOREIGN KEY `FK_756A2A8D32C8A3DE`');
        $this->addSql('ALTER TABLE rm_organization_member DROP FOREIGN KEY `FK_756A2A8DA76ED395`');
        $this->addSql('ALTER TABLE rm_rent_receipt DROP FOREIGN KEY `FK_B2B5CD35D3CA542C`');
        $this->addSql('ALTER TABLE rm_tenant DROP FOREIGN KEY `FK_4E59C46232C8A3DE`');
        $this->addSql('ALTER TABLE rm_tenant DROP FOREIGN KEY `FK_4E59C462A76ED395`');
        $this->addSql('ALTER TABLE rm_user DROP FOREIGN KEY `FK_8D93D64932C8A3DE`');
        $this->addSql('DROP TABLE rm_charge_type');
        $this->addSql('DROP TABLE rm_housing');
        $this->addSql('DROP TABLE rm_imputation');
        $this->addSql('DROP TABLE rm_lease');
        $this->addSql('DROP TABLE rm_lease_tenant');
        $this->addSql('DROP TABLE rm_organization');
        $this->addSql('DROP TABLE rm_organization_member');
        $this->addSql('DROP TABLE rm_rent_receipt');
        $this->addSql('DROP TABLE rm_tenant');
        $this->addSql('DROP TABLE rm_user');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rm_charge_type (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, is_recoverable TINYINT(1) NOT NULL, is_rent_component TINYINT(1) NOT NULL, periodicity VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, direction VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, comment LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_C41D3AF6A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE rm_housing (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, city VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, city_code VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, address VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, building VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, apartment_number VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, note LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, type VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, user_id INT NOT NULL, INDEX IDX_FB8142C3A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE rm_imputation (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(10, 2) NOT NULL, period_start DATE NOT NULL, period_end DATE DEFAULT NULL, note LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, housing_id INT NOT NULL, type_id INT NOT NULL, INDEX IDX_AE81A25AAD5873E3 (housing_id), INDEX IDX_AE81A25AC54C8C93 (type_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE rm_lease (id INT AUTO_INCREMENT NOT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, note LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, contract_file VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, housing_id INT NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_E6C77495AD5873E3 (housing_id), INDEX IDX_E6C77495A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE rm_lease_tenant (id INT AUTO_INCREMENT NOT NULL, percentage INT NOT NULL, active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, lease_id INT NOT NULL, tenant_id INT NOT NULL, INDEX IDX_32081F82D3CA542C (lease_id), INDEX IDX_32081F829033212A (tenant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE rm_organization (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, phone VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, address VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, city VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, city_code VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, note LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE rm_organization_member (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, joined_at DATETIME NOT NULL, invited_by VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, user_id INT NOT NULL, organization_id INT NOT NULL, INDEX IDX_756A2A8DA76ED395 (user_id), INDEX IDX_756A2A8D32C8A3DE (organization_id), UNIQUE INDEX unique_user_organization (user_id, organization_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE rm_rent_receipt (id INT AUTO_INCREMENT NOT NULL, period_start DATE NOT NULL, period_end DATE NOT NULL, rent_amount NUMERIC(10, 2) NOT NULL, recoverable_charges NUMERIC(10, 2) NOT NULL, total_due NUMERIC(10, 2) NOT NULL, total_paid NUMERIC(10, 2) NOT NULL, payment_method VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, generated_at DATETIME NOT NULL, pdf_file VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, note LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, lease_id INT NOT NULL, INDEX IDX_B2B5CD35D3CA542C (lease_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE rm_tenant (id INT AUTO_INCREMENT NOT NULL, firstname VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, lastname VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, phone VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, note LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT DEFAULT NULL, organization_id INT DEFAULT NULL, INDEX IDX_4E59C46232C8A3DE (organization_id), UNIQUE INDEX UNIQ_4E59C462E7927C74 (email), INDEX IDX_4E59C462A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE rm_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, roles JSON NOT NULL, password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, email_verified TINYINT(1) NOT NULL, email_verification_token VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, email_verification_token_expires_at DATETIME DEFAULT NULL, organization_id INT DEFAULT NULL, password_reset_code VARCHAR(6) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, password_reset_code_expires_at DATETIME DEFAULT NULL, INDEX IDX_8D93D64932C8A3DE (organization_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE rm_charge_type ADD CONSTRAINT `FK_C41D3AF6A76ED395` FOREIGN KEY (user_id) REFERENCES rm_user (id)');
        $this->addSql('ALTER TABLE rm_housing ADD CONSTRAINT `FK_FB8142C3A76ED395` FOREIGN KEY (user_id) REFERENCES rm_user (id)');
        $this->addSql('ALTER TABLE rm_imputation ADD CONSTRAINT `FK_AE81A25AAD5873E3` FOREIGN KEY (housing_id) REFERENCES rm_housing (id)');
        $this->addSql('ALTER TABLE rm_imputation ADD CONSTRAINT `FK_AE81A25AC54C8C93` FOREIGN KEY (type_id) REFERENCES rm_charge_type (id)');
        $this->addSql('ALTER TABLE rm_lease ADD CONSTRAINT `FK_E6C77495A76ED395` FOREIGN KEY (user_id) REFERENCES rm_user (id)');
        $this->addSql('ALTER TABLE rm_lease ADD CONSTRAINT `FK_E6C77495AD5873E3` FOREIGN KEY (housing_id) REFERENCES rm_housing (id)');
        $this->addSql('ALTER TABLE rm_lease_tenant ADD CONSTRAINT `FK_32081F829033212A` FOREIGN KEY (tenant_id) REFERENCES rm_tenant (id)');
        $this->addSql('ALTER TABLE rm_lease_tenant ADD CONSTRAINT `FK_32081F82D3CA542C` FOREIGN KEY (lease_id) REFERENCES rm_lease (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rm_organization_member ADD CONSTRAINT `FK_756A2A8D32C8A3DE` FOREIGN KEY (organization_id) REFERENCES rm_organization (id)');
        $this->addSql('ALTER TABLE rm_organization_member ADD CONSTRAINT `FK_756A2A8DA76ED395` FOREIGN KEY (user_id) REFERENCES rm_user (id)');
        $this->addSql('ALTER TABLE rm_rent_receipt ADD CONSTRAINT `FK_B2B5CD35D3CA542C` FOREIGN KEY (lease_id) REFERENCES rm_lease (id)');
        $this->addSql('ALTER TABLE rm_tenant ADD CONSTRAINT `FK_4E59C46232C8A3DE` FOREIGN KEY (organization_id) REFERENCES rm_organization (id)');
        $this->addSql('ALTER TABLE rm_tenant ADD CONSTRAINT `FK_4E59C462A76ED395` FOREIGN KEY (user_id) REFERENCES rm_user (id)');
        $this->addSql('ALTER TABLE rm_user ADD CONSTRAINT `FK_8D93D64932C8A3DE` FOREIGN KEY (organization_id) REFERENCES rm_organization (id)');
        $this->addSql('ALTER TABLE charge_type DROP FOREIGN KEY FK_C41D3AF6A76ED395');
        $this->addSql('ALTER TABLE housing DROP FOREIGN KEY FK_FB8142C3A76ED395');
        $this->addSql('ALTER TABLE imputation DROP FOREIGN KEY FK_AE81A25AAD5873E3');
        $this->addSql('ALTER TABLE imputation DROP FOREIGN KEY FK_AE81A25AC54C8C93');
        $this->addSql('ALTER TABLE lease DROP FOREIGN KEY FK_E6C77495AD5873E3');
        $this->addSql('ALTER TABLE lease DROP FOREIGN KEY FK_E6C77495A76ED395');
        $this->addSql('ALTER TABLE lease_tenant DROP FOREIGN KEY FK_32081F82D3CA542C');
        $this->addSql('ALTER TABLE lease_tenant DROP FOREIGN KEY FK_32081F829033212A');
        $this->addSql('ALTER TABLE organization_member DROP FOREIGN KEY FK_756A2A8DA76ED395');
        $this->addSql('ALTER TABLE organization_member DROP FOREIGN KEY FK_756A2A8D32C8A3DE');
        $this->addSql('ALTER TABLE rent_receipt DROP FOREIGN KEY FK_B2B5CD35D3CA542C');
        $this->addSql('ALTER TABLE tenant DROP FOREIGN KEY FK_4E59C462A76ED395');
        $this->addSql('ALTER TABLE tenant DROP FOREIGN KEY FK_4E59C46232C8A3DE');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64932C8A3DE');
        $this->addSql('DROP TABLE charge_type');
        $this->addSql('DROP TABLE housing');
        $this->addSql('DROP TABLE imputation');
        $this->addSql('DROP TABLE lease');
        $this->addSql('DROP TABLE lease_tenant');
        $this->addSql('DROP TABLE organization');
        $this->addSql('DROP TABLE organization_member');
        $this->addSql('DROP TABLE rent_receipt');
        $this->addSql('DROP TABLE tenant');
        $this->addSql('DROP TABLE user');
    }
}
