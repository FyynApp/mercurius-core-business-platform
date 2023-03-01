<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230301192055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE videos ADD video_folders_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE videos ADD CONSTRAINT FK_29AA6432FF12A996 FOREIGN KEY (video_folders_id) REFERENCES video_folders (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_29AA6432FF12A996 ON videos (video_folders_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE videos DROP FOREIGN KEY FK_29AA6432FF12A996');
        $this->addSql('DROP INDEX IDX_29AA6432FF12A996 ON videos');
        $this->addSql('ALTER TABLE videos DROP video_folders_id');
    }
}
