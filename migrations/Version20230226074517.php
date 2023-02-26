<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230226074517 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE organization_groups 
            (
                id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                organizations_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                name VARCHAR(256) NOT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                access_rights TEXT NOT NULL COMMENT \'(DC2Type:simple_array)\',
                INDEX IDX_F5E3E98586288A55 (organizations_id),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ');

        $this->addSql('ALTER TABLE organization_groups ADD CONSTRAINT FK_F5E3E98586288A55 FOREIGN KEY (organizations_id) REFERENCES organizations (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organization_groups DROP FOREIGN KEY FK_F5E3E98586288A55');
        $this->addSql('DROP TABLE organization_groups');
    }
}
