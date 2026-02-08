<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207201924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rm_credit (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, amount DOUBLE PRECISION NOT NULL, duration INT NOT NULL, start_date DATE NOT NULL, rate DOUBLE PRECISION NOT NULL, comment LONGTEXT DEFAULT NULL, housing_id INT DEFAULT NULL, INDEX IDX_B2C1C94AD5873E3 (housing_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE rm_credit ADD CONSTRAINT FK_B2C1C94AD5873E3 FOREIGN KEY (housing_id) REFERENCES rm_housing (id)');
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
        $this->addSql('ALTER TABLE rm_charge_type DROP FOREIGN KEY `FK_C41D3AF6A76ED395`');
        $this->addSql('DROP INDEX idx_c41d3af6a76ed395 ON rm_charge_type');
        $this->addSql('CREATE INDEX IDX_7210FA68A76ED395 ON rm_charge_type (user_id)');
        $this->addSql('ALTER TABLE rm_charge_type ADD CONSTRAINT `FK_C41D3AF6A76ED395` FOREIGN KEY (user_id) REFERENCES rm_user (id)');
        $this->addSql('ALTER TABLE rm_housing DROP FOREIGN KEY `FK_FB8142C3A76ED395`');
        $this->addSql('DROP INDEX idx_fb8142c3a76ed395 ON rm_housing');
        $this->addSql('CREATE INDEX IDX_56F127F7A76ED395 ON rm_housing (user_id)');
        $this->addSql('ALTER TABLE rm_housing ADD CONSTRAINT `FK_FB8142C3A76ED395` FOREIGN KEY (user_id) REFERENCES rm_user (id)');
        $this->addSql('ALTER TABLE rm_housing_document DROP FOREIGN KEY `FK_housing_doc_housing`');
        $this->addSql('ALTER TABLE rm_housing_document DROP FOREIGN KEY `FK_housing_doc_user`');
        $this->addSql('ALTER TABLE rm_housing_document CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_housing_doc_housing ON rm_housing_document');
        $this->addSql('CREATE INDEX IDX_EF53D08DAD5873E3 ON rm_housing_document (housing_id)');
        $this->addSql('DROP INDEX idx_housing_doc_user ON rm_housing_document');
        $this->addSql('CREATE INDEX IDX_EF53D08DA2B28FE8 ON rm_housing_document (uploaded_by_id)');
        $this->addSql('ALTER TABLE rm_housing_document ADD CONSTRAINT `FK_housing_doc_housing` FOREIGN KEY (housing_id) REFERENCES rm_housing (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rm_housing_document ADD CONSTRAINT `FK_housing_doc_user` FOREIGN KEY (uploaded_by_id) REFERENCES rm_user (id)');
        $this->addSql('ALTER TABLE rm_housing_event DROP FOREIGN KEY `FK_housing_event_author`');
        $this->addSql('ALTER TABLE rm_housing_event DROP FOREIGN KEY `FK_housing_event_housing`');
        $this->addSql('ALTER TABLE rm_housing_event CHANGE event_date event_date DATE NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_housing_event_housing ON rm_housing_event');
        $this->addSql('CREATE INDEX IDX_9C0B1F4EAD5873E3 ON rm_housing_event (housing_id)');
        $this->addSql('DROP INDEX idx_housing_event_author ON rm_housing_event');
        $this->addSql('CREATE INDEX IDX_9C0B1F4EF675F31B ON rm_housing_event (author_id)');
        $this->addSql('ALTER TABLE rm_housing_event ADD CONSTRAINT `FK_housing_event_author` FOREIGN KEY (author_id) REFERENCES rm_user (id)');
        $this->addSql('ALTER TABLE rm_housing_event ADD CONSTRAINT `FK_housing_event_housing` FOREIGN KEY (housing_id) REFERENCES rm_housing (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rm_imputation DROP FOREIGN KEY `FK_AE81A25AAD5873E3`');
        $this->addSql('ALTER TABLE rm_imputation DROP FOREIGN KEY `FK_AE81A25AC54C8C93`');
        $this->addSql('DROP INDEX idx_ae81a25aad5873e3 ON rm_imputation');
        $this->addSql('CREATE INDEX IDX_C56C0165AD5873E3 ON rm_imputation (housing_id)');
        $this->addSql('DROP INDEX idx_ae81a25ac54c8c93 ON rm_imputation');
        $this->addSql('CREATE INDEX IDX_C56C0165C54C8C93 ON rm_imputation (type_id)');
        $this->addSql('ALTER TABLE rm_imputation ADD CONSTRAINT `FK_AE81A25AAD5873E3` FOREIGN KEY (housing_id) REFERENCES rm_housing (id)');
        $this->addSql('ALTER TABLE rm_imputation ADD CONSTRAINT `FK_AE81A25AC54C8C93` FOREIGN KEY (type_id) REFERENCES rm_charge_type (id)');
        $this->addSql('ALTER TABLE rm_lease DROP FOREIGN KEY `FK_E6C77495A76ED395`');
        $this->addSql('ALTER TABLE rm_lease DROP FOREIGN KEY `FK_E6C77495AD5873E3`');
        $this->addSql('DROP INDEX idx_e6c77495ad5873e3 ON rm_lease');
        $this->addSql('CREATE INDEX IDX_BC0B5D0BAD5873E3 ON rm_lease (housing_id)');
        $this->addSql('DROP INDEX idx_e6c77495a76ed395 ON rm_lease');
        $this->addSql('CREATE INDEX IDX_BC0B5D0BA76ED395 ON rm_lease (user_id)');
        $this->addSql('ALTER TABLE rm_lease ADD CONSTRAINT `FK_E6C77495A76ED395` FOREIGN KEY (user_id) REFERENCES rm_user (id)');
        $this->addSql('ALTER TABLE rm_lease ADD CONSTRAINT `FK_E6C77495AD5873E3` FOREIGN KEY (housing_id) REFERENCES rm_housing (id)');
        $this->addSql('ALTER TABLE rm_lease_tenant DROP FOREIGN KEY `FK_32081F829033212A`');
        $this->addSql('ALTER TABLE rm_lease_tenant DROP FOREIGN KEY `FK_32081F82D3CA542C`');
        $this->addSql('DROP INDEX idx_32081f82d3ca542c ON rm_lease_tenant');
        $this->addSql('CREATE INDEX IDX_2509AC01D3CA542C ON rm_lease_tenant (lease_id)');
        $this->addSql('DROP INDEX idx_32081f829033212a ON rm_lease_tenant');
        $this->addSql('CREATE INDEX IDX_2509AC019033212A ON rm_lease_tenant (tenant_id)');
        $this->addSql('ALTER TABLE rm_lease_tenant ADD CONSTRAINT `FK_32081F829033212A` FOREIGN KEY (tenant_id) REFERENCES rm_tenant (id)');
        $this->addSql('ALTER TABLE rm_lease_tenant ADD CONSTRAINT `FK_32081F82D3CA542C` FOREIGN KEY (lease_id) REFERENCES rm_lease (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rm_organization_member DROP FOREIGN KEY `FK_756A2A8D32C8A3DE`');
        $this->addSql('ALTER TABLE rm_organization_member DROP FOREIGN KEY `FK_756A2A8DA76ED395`');
        $this->addSql('DROP INDEX idx_756a2a8da76ed395 ON rm_organization_member');
        $this->addSql('CREATE INDEX IDX_A064FDAFA76ED395 ON rm_organization_member (user_id)');
        $this->addSql('DROP INDEX idx_756a2a8d32c8a3de ON rm_organization_member');
        $this->addSql('CREATE INDEX IDX_A064FDAF32C8A3DE ON rm_organization_member (organization_id)');
        $this->addSql('ALTER TABLE rm_organization_member ADD CONSTRAINT `FK_756A2A8D32C8A3DE` FOREIGN KEY (organization_id) REFERENCES rm_organization (id)');
        $this->addSql('ALTER TABLE rm_organization_member ADD CONSTRAINT `FK_756A2A8DA76ED395` FOREIGN KEY (user_id) REFERENCES rm_user (id)');
        $this->addSql('ALTER TABLE rm_rent_receipt DROP FOREIGN KEY `FK_B2B5CD35D3CA542C`');
        $this->addSql('DROP INDEX idx_b2b5cd35d3ca542c ON rm_rent_receipt');
        $this->addSql('CREATE INDEX IDX_A5B47EB6D3CA542C ON rm_rent_receipt (lease_id)');
        $this->addSql('ALTER TABLE rm_rent_receipt ADD CONSTRAINT `FK_B2B5CD35D3CA542C` FOREIGN KEY (lease_id) REFERENCES rm_lease (id)');
        $this->addSql('ALTER TABLE rm_tenant DROP FOREIGN KEY `FK_4E59C46232C8A3DE`');
        $this->addSql('ALTER TABLE rm_tenant DROP FOREIGN KEY `FK_4E59C462A76ED395`');
        $this->addSql('DROP INDEX uniq_4e59c462e7927c74 ON rm_tenant');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_59B4B608E7927C74 ON rm_tenant (email)');
        $this->addSql('DROP INDEX idx_4e59c462a76ed395 ON rm_tenant');
        $this->addSql('CREATE INDEX IDX_59B4B608A76ED395 ON rm_tenant (user_id)');
        $this->addSql('DROP INDEX idx_4e59c46232c8a3de ON rm_tenant');
        $this->addSql('CREATE INDEX IDX_59B4B60832C8A3DE ON rm_tenant (organization_id)');
        $this->addSql('ALTER TABLE rm_tenant ADD CONSTRAINT `FK_4E59C46232C8A3DE` FOREIGN KEY (organization_id) REFERENCES rm_organization (id)');
        $this->addSql('ALTER TABLE rm_tenant ADD CONSTRAINT `FK_4E59C462A76ED395` FOREIGN KEY (user_id) REFERENCES rm_user (id)');
        $this->addSql('ALTER TABLE rm_user DROP FOREIGN KEY `FK_8D93D64932C8A3DE`');
        $this->addSql('DROP INDEX idx_8d93d64932c8a3de ON rm_user');
        $this->addSql('CREATE INDEX IDX_446553B732C8A3DE ON rm_user (organization_id)');
        $this->addSql('ALTER TABLE rm_user ADD CONSTRAINT `FK_8D93D64932C8A3DE` FOREIGN KEY (organization_id) REFERENCES rm_organization (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE charge_type (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, is_recoverable TINYINT(1) NOT NULL, is_rent_component TINYINT(1) NOT NULL, periodicity VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, direction VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, comment LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_C41D3AF6A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE housing (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, city VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, city_code VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, address VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, building VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, apartment_number VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, note LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, type VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, user_id INT NOT NULL, INDEX IDX_FB8142C3A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE imputation (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(10, 2) NOT NULL, period_start DATE NOT NULL, period_end DATE DEFAULT NULL, note LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, housing_id INT NOT NULL, type_id INT NOT NULL, INDEX IDX_AE81A25AC54C8C93 (type_id), INDEX IDX_AE81A25AAD5873E3 (housing_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE lease (id INT AUTO_INCREMENT NOT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, note LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, contract_file VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, housing_id INT NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_E6C77495AD5873E3 (housing_id), INDEX IDX_E6C77495A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE lease_tenant (id INT AUTO_INCREMENT NOT NULL, percentage INT NOT NULL, active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, lease_id INT NOT NULL, tenant_id INT NOT NULL, INDEX IDX_32081F82D3CA542C (lease_id), INDEX IDX_32081F829033212A (tenant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE organization (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, phone VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, address VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, city VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, city_code VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, note LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE organization_member (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, joined_at DATETIME NOT NULL, invited_by VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, user_id INT NOT NULL, organization_id INT NOT NULL, INDEX IDX_756A2A8DA76ED395 (user_id), INDEX IDX_756A2A8D32C8A3DE (organization_id), UNIQUE INDEX unique_user_organization (user_id, organization_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE rent_receipt (id INT AUTO_INCREMENT NOT NULL, period_start DATE NOT NULL, period_end DATE NOT NULL, rent_amount NUMERIC(10, 2) NOT NULL, recoverable_charges NUMERIC(10, 2) NOT NULL, total_due NUMERIC(10, 2) NOT NULL, total_paid NUMERIC(10, 2) NOT NULL, payment_method VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, generated_at DATETIME NOT NULL, pdf_file VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, note LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, lease_id INT NOT NULL, INDEX IDX_B2B5CD35D3CA542C (lease_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE tenant (id INT AUTO_INCREMENT NOT NULL, firstname VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, lastname VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, phone VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, note LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT DEFAULT NULL, organization_id INT DEFAULT NULL, INDEX IDX_4E59C462A76ED395 (user_id), INDEX IDX_4E59C46232C8A3DE (organization_id), UNIQUE INDEX UNIQ_4E59C462E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, roles JSON NOT NULL, password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, email_verified TINYINT(1) NOT NULL, email_verification_token VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, email_verification_token_expires_at DATETIME DEFAULT NULL, password_reset_code VARCHAR(6) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, password_reset_code_expires_at DATETIME DEFAULT NULL, organization_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), INDEX IDX_8D93D64932C8A3DE (organization_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE rm_credit DROP FOREIGN KEY FK_B2C1C94AD5873E3');
        $this->addSql('DROP TABLE rm_credit');
        $this->addSql('ALTER TABLE rm_charge_type DROP FOREIGN KEY FK_7210FA68A76ED395');
        $this->addSql('DROP INDEX idx_7210fa68a76ed395 ON rm_charge_type');
        $this->addSql('CREATE INDEX IDX_C41D3AF6A76ED395 ON rm_charge_type (user_id)');
        $this->addSql('ALTER TABLE rm_charge_type ADD CONSTRAINT FK_7210FA68A76ED395 FOREIGN KEY (user_id) REFERENCES rm_user (id)');
        $this->addSql('ALTER TABLE rm_housing DROP FOREIGN KEY FK_56F127F7A76ED395');
        $this->addSql('DROP INDEX idx_56f127f7a76ed395 ON rm_housing');
        $this->addSql('CREATE INDEX IDX_FB8142C3A76ED395 ON rm_housing (user_id)');
        $this->addSql('ALTER TABLE rm_housing ADD CONSTRAINT FK_56F127F7A76ED395 FOREIGN KEY (user_id) REFERENCES rm_user (id)');
        $this->addSql('ALTER TABLE rm_housing_document DROP FOREIGN KEY FK_EF53D08DAD5873E3');
        $this->addSql('ALTER TABLE rm_housing_document DROP FOREIGN KEY FK_EF53D08DA2B28FE8');
        $this->addSql('ALTER TABLE rm_housing_document CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('DROP INDEX idx_ef53d08dad5873e3 ON rm_housing_document');
        $this->addSql('CREATE INDEX IDX_housing_doc_housing ON rm_housing_document (housing_id)');
        $this->addSql('DROP INDEX idx_ef53d08da2b28fe8 ON rm_housing_document');
        $this->addSql('CREATE INDEX IDX_housing_doc_user ON rm_housing_document (uploaded_by_id)');
        $this->addSql('ALTER TABLE rm_housing_document ADD CONSTRAINT FK_EF53D08DAD5873E3 FOREIGN KEY (housing_id) REFERENCES rm_housing (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rm_housing_document ADD CONSTRAINT FK_EF53D08DA2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES rm_user (id)');
        $this->addSql('ALTER TABLE rm_housing_event DROP FOREIGN KEY FK_9C0B1F4EAD5873E3');
        $this->addSql('ALTER TABLE rm_housing_event DROP FOREIGN KEY FK_9C0B1F4EF675F31B');
        $this->addSql('ALTER TABLE rm_housing_event CHANGE event_date event_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('DROP INDEX idx_9c0b1f4ead5873e3 ON rm_housing_event');
        $this->addSql('CREATE INDEX IDX_housing_event_housing ON rm_housing_event (housing_id)');
        $this->addSql('DROP INDEX idx_9c0b1f4ef675f31b ON rm_housing_event');
        $this->addSql('CREATE INDEX IDX_housing_event_author ON rm_housing_event (author_id)');
        $this->addSql('ALTER TABLE rm_housing_event ADD CONSTRAINT FK_9C0B1F4EAD5873E3 FOREIGN KEY (housing_id) REFERENCES rm_housing (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rm_housing_event ADD CONSTRAINT FK_9C0B1F4EF675F31B FOREIGN KEY (author_id) REFERENCES rm_user (id)');
        $this->addSql('ALTER TABLE rm_imputation DROP FOREIGN KEY FK_C56C0165AD5873E3');
        $this->addSql('ALTER TABLE rm_imputation DROP FOREIGN KEY FK_C56C0165C54C8C93');
        $this->addSql('DROP INDEX idx_c56c0165ad5873e3 ON rm_imputation');
        $this->addSql('CREATE INDEX IDX_AE81A25AAD5873E3 ON rm_imputation (housing_id)');
        $this->addSql('DROP INDEX idx_c56c0165c54c8c93 ON rm_imputation');
        $this->addSql('CREATE INDEX IDX_AE81A25AC54C8C93 ON rm_imputation (type_id)');
        $this->addSql('ALTER TABLE rm_imputation ADD CONSTRAINT FK_C56C0165AD5873E3 FOREIGN KEY (housing_id) REFERENCES rm_housing (id)');
        $this->addSql('ALTER TABLE rm_imputation ADD CONSTRAINT FK_C56C0165C54C8C93 FOREIGN KEY (type_id) REFERENCES rm_charge_type (id)');
        $this->addSql('ALTER TABLE rm_lease DROP FOREIGN KEY FK_BC0B5D0BAD5873E3');
        $this->addSql('ALTER TABLE rm_lease DROP FOREIGN KEY FK_BC0B5D0BA76ED395');
        $this->addSql('DROP INDEX idx_bc0b5d0bad5873e3 ON rm_lease');
        $this->addSql('CREATE INDEX IDX_E6C77495AD5873E3 ON rm_lease (housing_id)');
        $this->addSql('DROP INDEX idx_bc0b5d0ba76ed395 ON rm_lease');
        $this->addSql('CREATE INDEX IDX_E6C77495A76ED395 ON rm_lease (user_id)');
        $this->addSql('ALTER TABLE rm_lease ADD CONSTRAINT FK_BC0B5D0BAD5873E3 FOREIGN KEY (housing_id) REFERENCES rm_housing (id)');
        $this->addSql('ALTER TABLE rm_lease ADD CONSTRAINT FK_BC0B5D0BA76ED395 FOREIGN KEY (user_id) REFERENCES rm_user (id)');
        $this->addSql('ALTER TABLE rm_lease_tenant DROP FOREIGN KEY FK_2509AC01D3CA542C');
        $this->addSql('ALTER TABLE rm_lease_tenant DROP FOREIGN KEY FK_2509AC019033212A');
        $this->addSql('DROP INDEX idx_2509ac01d3ca542c ON rm_lease_tenant');
        $this->addSql('CREATE INDEX IDX_32081F82D3CA542C ON rm_lease_tenant (lease_id)');
        $this->addSql('DROP INDEX idx_2509ac019033212a ON rm_lease_tenant');
        $this->addSql('CREATE INDEX IDX_32081F829033212A ON rm_lease_tenant (tenant_id)');
        $this->addSql('ALTER TABLE rm_lease_tenant ADD CONSTRAINT FK_2509AC01D3CA542C FOREIGN KEY (lease_id) REFERENCES rm_lease (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rm_lease_tenant ADD CONSTRAINT FK_2509AC019033212A FOREIGN KEY (tenant_id) REFERENCES rm_tenant (id)');
        $this->addSql('ALTER TABLE rm_organization_member DROP FOREIGN KEY FK_A064FDAFA76ED395');
        $this->addSql('ALTER TABLE rm_organization_member DROP FOREIGN KEY FK_A064FDAF32C8A3DE');
        $this->addSql('DROP INDEX idx_a064fdafa76ed395 ON rm_organization_member');
        $this->addSql('CREATE INDEX IDX_756A2A8DA76ED395 ON rm_organization_member (user_id)');
        $this->addSql('DROP INDEX idx_a064fdaf32c8a3de ON rm_organization_member');
        $this->addSql('CREATE INDEX IDX_756A2A8D32C8A3DE ON rm_organization_member (organization_id)');
        $this->addSql('ALTER TABLE rm_organization_member ADD CONSTRAINT FK_A064FDAFA76ED395 FOREIGN KEY (user_id) REFERENCES rm_user (id)');
        $this->addSql('ALTER TABLE rm_organization_member ADD CONSTRAINT FK_A064FDAF32C8A3DE FOREIGN KEY (organization_id) REFERENCES rm_organization (id)');
        $this->addSql('ALTER TABLE rm_rent_receipt DROP FOREIGN KEY FK_A5B47EB6D3CA542C');
        $this->addSql('DROP INDEX idx_a5b47eb6d3ca542c ON rm_rent_receipt');
        $this->addSql('CREATE INDEX IDX_B2B5CD35D3CA542C ON rm_rent_receipt (lease_id)');
        $this->addSql('ALTER TABLE rm_rent_receipt ADD CONSTRAINT FK_A5B47EB6D3CA542C FOREIGN KEY (lease_id) REFERENCES rm_lease (id)');
        $this->addSql('ALTER TABLE rm_tenant DROP FOREIGN KEY FK_59B4B608A76ED395');
        $this->addSql('ALTER TABLE rm_tenant DROP FOREIGN KEY FK_59B4B60832C8A3DE');
        $this->addSql('DROP INDEX idx_59b4b608a76ed395 ON rm_tenant');
        $this->addSql('CREATE INDEX IDX_4E59C462A76ED395 ON rm_tenant (user_id)');
        $this->addSql('DROP INDEX idx_59b4b60832c8a3de ON rm_tenant');
        $this->addSql('CREATE INDEX IDX_4E59C46232C8A3DE ON rm_tenant (organization_id)');
        $this->addSql('DROP INDEX uniq_59b4b608e7927c74 ON rm_tenant');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4E59C462E7927C74 ON rm_tenant (email)');
        $this->addSql('ALTER TABLE rm_tenant ADD CONSTRAINT FK_59B4B608A76ED395 FOREIGN KEY (user_id) REFERENCES rm_user (id)');
        $this->addSql('ALTER TABLE rm_tenant ADD CONSTRAINT FK_59B4B60832C8A3DE FOREIGN KEY (organization_id) REFERENCES rm_organization (id)');
        $this->addSql('ALTER TABLE rm_user DROP FOREIGN KEY FK_446553B732C8A3DE');
        $this->addSql('DROP INDEX idx_446553b732c8a3de ON rm_user');
        $this->addSql('CREATE INDEX IDX_8D93D64932C8A3DE ON rm_user (organization_id)');
        $this->addSql('ALTER TABLE rm_user ADD CONSTRAINT FK_446553B732C8A3DE FOREIGN KEY (organization_id) REFERENCES rm_organization (id)');
    }
}
