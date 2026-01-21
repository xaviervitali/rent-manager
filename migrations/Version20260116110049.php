<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260116110049 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }
public function up(Schema $schema): void
{
    $this->addSql('RENAME TABLE charge_type TO rm_charge_type');
    $this->addSql('RENAME TABLE housing TO rm_housing');
    $this->addSql('RENAME TABLE imputation TO rm_imputation');
    $this->addSql('RENAME TABLE lease TO rm_lease');
    $this->addSql('RENAME TABLE lease_tenant TO rm_lease_tenant');
    $this->addSql('RENAME TABLE organization TO rm_organization');
    $this->addSql('RENAME TABLE organization_member TO rm_organization_member');
    $this->addSql('RENAME TABLE rent_receipt TO rm_rent_receipt');
    $this->addSql('RENAME TABLE tenant TO rm_tenant');
    $this->addSql('RENAME TABLE user TO rm_user');
}


    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
