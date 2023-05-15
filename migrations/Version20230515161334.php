<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230515161334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE video_folders ADD is_default_for_administrator_recordings TINYINT(1) NOT NULL, ADD is_visible_for_non_administrators TINYINT(1) NOT NULL');
        $this->addSql('UPDATE video_folders SET is_default_for_administrator_recordings = FALSE, is_visible_for_non_administrators = TRUE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE video_folders DROP is_default_for_administrator_recordings, DROP is_visible_for_non_administrators');
    }
}
