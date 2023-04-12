<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230412154348 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE process_log_entries (
                id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                recording_sessions_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\',
                recordings_video_uploads_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\',
                videos_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\',
                type VARCHAR(255) NOT NULL,
                started_at DATETIME NOT NULL,
                finished_at DATETIME DEFAULT NULL,
                latest_error_message VARCHAR(4096) DEFAULT NULL,
                INDEX IDX_1B404843D98B1C16 (recording_sessions_id),
                INDEX IDX_1B4048434ADB3485 (recordings_video_uploads_id),
                INDEX IDX_1B404843763C10B2 (videos_id),
                INDEX started_at_idx (started_at),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ');

        $this->addSql('ALTER TABLE process_log_entries ADD CONSTRAINT FK_1B404843D98B1C16 FOREIGN KEY (recording_sessions_id) REFERENCES recording_sessions (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE process_log_entries ADD CONSTRAINT FK_1B4048434ADB3485 FOREIGN KEY (recordings_video_uploads_id) REFERENCES recordings_video_uploads (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE process_log_entries ADD CONSTRAINT FK_1B404843763C10B2 FOREIGN KEY (videos_id) REFERENCES videos (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE process_log_entries DROP FOREIGN KEY FK_1B404843D98B1C16');
        $this->addSql('ALTER TABLE process_log_entries DROP FOREIGN KEY FK_1B4048434ADB3485');
        $this->addSql('ALTER TABLE process_log_entries DROP FOREIGN KEY FK_1B404843763C10B2');
        $this->addSql('DROP TABLE process_log_entries');
    }
}
