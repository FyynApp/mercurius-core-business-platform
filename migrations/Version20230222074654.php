<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230222074654 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE organization_invitations (
                id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                owner_users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                email VARCHAR(256) NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX IDX_137BB4D571530DFE (owner_users_id),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE organizations (
                id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                owning_users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                name VARCHAR(256) DEFAULT NULL,
                UNIQUE INDEX UNIQ_427C1C7F91CA5BAF (owning_users_id),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ');

        $this->addSql('ALTER TABLE organization_invitations ADD CONSTRAINT FK_137BB4D571530DFE FOREIGN KEY (owner_users_id) REFERENCES organizations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE organizations ADD CONSTRAINT FK_427C1C7F91CA5BAF FOREIGN KEY (owning_users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users ADD organizations_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E986288A55 FOREIGN KEY (organizations_id) REFERENCES organizations (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_1483A5E986288A55 ON users (organizations_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E986288A55');
        $this->addSql('ALTER TABLE organization_invitations DROP FOREIGN KEY FK_137BB4D571530DFE');
        $this->addSql('ALTER TABLE organizations DROP FOREIGN KEY FK_427C1C7F91CA5BAF');
        $this->addSql('DROP TABLE organization_invitations');
        $this->addSql('DROP TABLE organizations');
        $this->addSql('DROP INDEX IDX_1483A5E986288A55 ON users');
        $this->addSql('ALTER TABLE users DROP organizations_id');
    }
}
