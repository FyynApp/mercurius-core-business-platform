<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220721093432 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recording_session_full_videos DROP video_blob, DROP preview_image_blob;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recording_session_full_videos ADD video_blob LONGBLOB NOT NULL, ADD preview_image_blob LONGBLOB NOT NULL;');
    }
}
