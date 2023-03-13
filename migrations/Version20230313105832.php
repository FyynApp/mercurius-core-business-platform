<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230313105832 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            CREATE TABLE audio_transcription_happy_scribe_exports
            (
                id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                audio_transcription_happy_scribe_transcriptions_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                created_at DATETIME NOT NULL,
                state VARCHAR(32) NOT NULL,
                format VARCHAR(32) NOT NULL,
                happy_scribe_export_id VARCHAR(256) NOT NULL,
                download_link VARCHAR(4192) DEFAULT NULL,
                INDEX IDX_285DB4A9253B1B25 (audio_transcription_happy_scribe_transcriptions_id),
                INDEX created_at_idx (created_at),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE audio_transcription_happy_scribe_transcriptions
            (
                id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                audio_transcriptions_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                created_at DATETIME NOT NULL,
                state VARCHAR(32) NOT NULL,
                audio_transcription_bcp47_language_code VARCHAR(16) NOT NULL,
                happy_scribe_transcription_id VARCHAR(256) NOT NULL,
                INDEX IDX_399005BF7B734100 (audio_transcriptions_id),
                INDEX created_at_idx (created_at),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE audio_transcription_happy_scribe_translation_tasks
            (
                id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                audio_transcription_happy_scribe_transcriptions_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                created_at DATETIME NOT NULL,
                state VARCHAR(32) NOT NULL,
                audio_transcription_bcp47_language_code VARCHAR(16) NOT NULL,
                happy_scribe_translation_task_id VARCHAR(256) NOT NULL,
                translated_transcription_id VARCHAR(256) DEFAULT NULL,
                INDEX IDX_2768444253B1B25 (audio_transcription_happy_scribe_transcriptions_id),
                INDEX created_at_idx (created_at),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE audio_transcription_suggested_summaries
            (
                id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                audio_transcriptions_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                audio_transcription_bcp47_language_code VARCHAR(16) NOT NULL,
                summary_content LONGTEXT NOT NULL,
                INDEX IDX_FB1FCE697B734100 (audio_transcriptions_id),
                INDEX main_idx (audio_transcriptions_id, audio_transcription_bcp47_language_code),
                FULLTEXT INDEX summary_content_fulltext_idx (summary_content),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE audio_transcription_web_vtts
            (
                id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                audio_transcriptions_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                audio_transcription_bcp47_language_code VARCHAR(16) NOT NULL,
                vtt_content LONGTEXT NOT NULL,
                INDEX IDX_5E9C4EE7B734100 (audio_transcriptions_id),
                INDEX main_idx (audio_transcriptions_id, audio_transcription_bcp47_language_code),
                FULLTEXT INDEX vtt_content_fulltext_idx (vtt_content),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE audio_transcription_words
            (
                id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                audio_transcriptions_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                audio_transcription_bcp47_language_code VARCHAR(16) NOT NULL,
                speaker VARCHAR(256) NOT NULL,
                speaker_number INT DEFAULT NULL,
                word VARCHAR(256) NOT NULL,
                word_type VARCHAR(64) NOT NULL,
                data_start DOUBLE PRECISION NOT NULL,
                data_end DOUBLE PRECISION NOT NULL,
                confidence DOUBLE PRECISION NOT NULL,
                INDEX IDX_3480B7397B734100 (audio_transcriptions_id),
                INDEX main_idx (audio_transcriptions_id, audio_transcription_bcp47_language_code, speaker, data_start),
                INDEX word_idx (word), PRIMARY KEY(id))
                DEFAULT CHARACTER SET utf8mb4
                COLLATE `utf8mb4_unicode_ci`
                ENGINE = InnoDB
            ');

        $this->addSql('
            CREATE TABLE audio_transcriptions
            (
                id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                videos_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                created_at DATETIME NOT NULL,
                original_language_bcp47_language_code VARCHAR(16) NOT NULL,
                INDEX IDX_B02A10D1763C10B2 (videos_id),
                INDEX created_at_idx (created_at),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ');

        $this->addSql('ALTER TABLE audio_transcription_happy_scribe_exports ADD CONSTRAINT FK_285DB4A9253B1B25 FOREIGN KEY (audio_transcription_happy_scribe_transcriptions_id) REFERENCES audio_transcription_happy_scribe_transcriptions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE audio_transcription_happy_scribe_transcriptions ADD CONSTRAINT FK_399005BF7B734100 FOREIGN KEY (audio_transcriptions_id) REFERENCES audio_transcriptions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE audio_transcription_happy_scribe_translation_tasks ADD CONSTRAINT FK_2768444253B1B25 FOREIGN KEY (audio_transcription_happy_scribe_transcriptions_id) REFERENCES audio_transcription_happy_scribe_transcriptions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE audio_transcription_suggested_summaries ADD CONSTRAINT FK_FB1FCE697B734100 FOREIGN KEY (audio_transcriptions_id) REFERENCES audio_transcriptions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE audio_transcription_web_vtts ADD CONSTRAINT FK_5E9C4EE7B734100 FOREIGN KEY (audio_transcriptions_id) REFERENCES audio_transcriptions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE audio_transcription_words ADD CONSTRAINT FK_3480B7397B734100 FOREIGN KEY (audio_transcriptions_id) REFERENCES audio_transcriptions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE audio_transcriptions ADD CONSTRAINT FK_B02A10D1763C10B2 FOREIGN KEY (videos_id) REFERENCES videos (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audio_transcription_happy_scribe_exports DROP FOREIGN KEY FK_285DB4A9253B1B25');
        $this->addSql('ALTER TABLE audio_transcription_happy_scribe_transcriptions DROP FOREIGN KEY FK_399005BF7B734100');
        $this->addSql('ALTER TABLE audio_transcription_happy_scribe_translation_tasks DROP FOREIGN KEY FK_2768444253B1B25');
        $this->addSql('ALTER TABLE audio_transcription_suggested_summaries DROP FOREIGN KEY FK_FB1FCE697B734100');
        $this->addSql('ALTER TABLE audio_transcription_web_vtts DROP FOREIGN KEY FK_5E9C4EE7B734100');
        $this->addSql('ALTER TABLE audio_transcription_words DROP FOREIGN KEY FK_3480B7397B734100');
        $this->addSql('ALTER TABLE audio_transcriptions DROP FOREIGN KEY FK_B02A10D1763C10B2');
        $this->addSql('DROP TABLE audio_transcription_happy_scribe_exports');
        $this->addSql('DROP TABLE audio_transcription_happy_scribe_transcriptions');
        $this->addSql('DROP TABLE audio_transcription_happy_scribe_translation_tasks');
        $this->addSql('DROP TABLE audio_transcription_suggested_summaries');
        $this->addSql('DROP TABLE audio_transcription_web_vtts');
        $this->addSql('DROP TABLE audio_transcription_words');
        $this->addSql('DROP TABLE audio_transcriptions');
    }
}
