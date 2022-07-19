<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220719121544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE recording_session_full_videos
            (
                id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\',
                recording_sessions_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\',
                mime_type VARCHAR(32) NOT NULL,
                video_blob LONGBLOB NOT NULL,
                UNIQUE INDEX UNIQ_8DFD21E9D98B1C16 (recording_sessions_id),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
            ;
        ');
        $this->addSql('ALTER TABLE recording_session_full_videos ADD CONSTRAINT FK_8DFD21E9D98B1C16 FOREIGN KEY (recording_sessions_id) REFERENCES recording_sessions (id) ON DELETE CASCADE;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE recording_session_full_videos;');
    }
}
