<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220802105829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recording_session_video_chunks ADD created_at DATETIME NOT NULL');
        $this->addSql('CREATE INDEX created_at_idx ON recording_session_video_chunks (created_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX created_at_idx ON recording_session_video_chunks');
        $this->addSql('ALTER TABLE recording_session_video_chunks DROP created_at');
    }
}
