<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230111142213 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE video_mailings
            (
                id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)',
                users_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)',
                created_at DATETIME NOT NULL,
                INDEX IDX_3F61587567B3B43D (users_id),
                INDEX created_at_idx (created_at),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ");
        $this->addSql('ALTER TABLE video_mailings ADD CONSTRAINT FK_3F61587567B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE video_mailings DROP FOREIGN KEY FK_3F61587567B3B43D');
        $this->addSql('DROP TABLE video_mailings');
    }
}
