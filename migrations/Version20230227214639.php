<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230227214639 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users CHANGE currently_active_organizations_id currently_active_organizations_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users CHANGE currently_active_organizations_id currently_active_organizations_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
    }
}
