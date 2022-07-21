<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220721091808 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recording_session_video_chunks DROP video_blob;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recording_session_video_chunks ADD video_blob LONGBLOB NOT NULL;');
    }
}
