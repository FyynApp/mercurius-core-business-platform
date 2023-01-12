<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230111150003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE video_mailings ADD videos_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)'");
        $this->addSql('ALTER TABLE video_mailings ADD CONSTRAINT FK_3F615875763C10B2 FOREIGN KEY (videos_id) REFERENCES videos (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_3F615875763C10B2 ON video_mailings (videos_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE video_mailings DROP FOREIGN KEY FK_3F615875763C10B2');
        $this->addSql('DROP INDEX IDX_3F615875763C10B2 ON video_mailings');
        $this->addSql('ALTER TABLE video_mailings DROP videos_id');
    }
}
