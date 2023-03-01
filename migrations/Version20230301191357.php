<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230301191357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE video_folders
            (
                id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                users_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\',
                organizations_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                name VARCHAR(256) NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX IDX_1BDA3AA267B3B43D (users_id),
                INDEX IDX_1BDA3AA286288A55 (organizations_id),
                INDEX organizations_id_name_idx (organizations_id, name),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ');
        $this->addSql('ALTER TABLE video_folders ADD CONSTRAINT FK_1BDA3AA267B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE video_folders ADD CONSTRAINT FK_1BDA3AA286288A55 FOREIGN KEY (organizations_id) REFERENCES organizations (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE video_folders DROP FOREIGN KEY FK_1BDA3AA267B3B43D');
        $this->addSql('ALTER TABLE video_folders DROP FOREIGN KEY FK_1BDA3AA286288A55');
        $this->addSql('DROP TABLE video_folders');
    }
}
