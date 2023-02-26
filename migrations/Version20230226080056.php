<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230226080056 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE users_organization_groups
            (
                users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                organization_groups_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                INDEX IDX_977D3B1F67B3B43D (users_id),
                INDEX IDX_977D3B1F3700E7C9 (organization_groups_id),
                PRIMARY KEY(users_id, organization_groups_id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ');
        $this->addSql('ALTER TABLE users_organization_groups ADD CONSTRAINT FK_977D3B1F67B3B43D FOREIGN KEY (users_id) REFERENCES organization_groups (id)');
        $this->addSql('ALTER TABLE users_organization_groups ADD CONSTRAINT FK_977D3B1F3700E7C9 FOREIGN KEY (organization_groups_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users_organization_groups DROP FOREIGN KEY FK_977D3B1F67B3B43D');
        $this->addSql('ALTER TABLE users_organization_groups DROP FOREIGN KEY FK_977D3B1F3700E7C9');
        $this->addSql('DROP TABLE users_organization_groups');
    }
}
