<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230310164939 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recording_requests ADD request_videos_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', ADD request_text LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE recording_requests ADD CONSTRAINT FK_D8B6801E3DCDE115 FOREIGN KEY (request_videos_id) REFERENCES videos (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_D8B6801E3DCDE115 ON recording_requests (request_videos_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recording_requests DROP FOREIGN KEY FK_D8B6801E3DCDE115');
        $this->addSql('DROP INDEX IDX_D8B6801E3DCDE115 ON recording_requests');
        $this->addSql('ALTER TABLE recording_requests DROP request_videos_id, DROP request_text');
    }
}
