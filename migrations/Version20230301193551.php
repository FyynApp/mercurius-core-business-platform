<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230301193551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE video_folders ADD parent_video_folders_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE video_folders ADD CONSTRAINT FK_1BDA3AA24B8EDBA5 FOREIGN KEY (parent_video_folders_id) REFERENCES video_folders (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_1BDA3AA24B8EDBA5 ON video_folders (parent_video_folders_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE video_folders DROP FOREIGN KEY FK_1BDA3AA24B8EDBA5');
        $this->addSql('DROP INDEX IDX_1BDA3AA24B8EDBA5 ON video_folders');
        $this->addSql('ALTER TABLE video_folders DROP parent_video_folders_id');
    }
}
