<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230530192522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audio_transcription_happy_scribe_transcriptions CHANGE audio_transcription_bcp47_language_code bcp47_language_code VARCHAR(16) NOT NULL');
        $this->addSql('ALTER TABLE audio_transcription_happy_scribe_translation_tasks CHANGE audio_transcription_bcp47_language_code bcp47_language_code VARCHAR(16) NOT NULL');
        $this->addSql('DROP INDEX main_idx ON audio_transcription_suggested_summaries');
        $this->addSql('ALTER TABLE audio_transcription_suggested_summaries CHANGE audio_transcription_bcp47_language_code bcp47_language_code VARCHAR(16) NOT NULL');
        $this->addSql('CREATE INDEX main_idx ON audio_transcription_suggested_summaries (audio_transcriptions_id, bcp47_language_code)');
        $this->addSql('DROP INDEX main_idx ON audio_transcription_web_vtts');
        $this->addSql('ALTER TABLE audio_transcription_web_vtts CHANGE audio_transcription_bcp47_language_code bcp47_language_code VARCHAR(16) NOT NULL');
        $this->addSql('CREATE INDEX main_idx ON audio_transcription_web_vtts (audio_transcriptions_id, bcp47_language_code)');
        $this->addSql('DROP INDEX main_idx ON audio_transcription_words');
        $this->addSql('ALTER TABLE audio_transcription_words CHANGE audio_transcription_bcp47_language_code bcp47_language_code VARCHAR(16) NOT NULL');
        $this->addSql('CREATE INDEX main_idx ON audio_transcription_words (audio_transcriptions_id, bcp47_language_code, speaker, data_start)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX main_idx ON audio_transcription_web_vtts');
        $this->addSql('ALTER TABLE audio_transcription_web_vtts CHANGE bcp47_language_code audio_transcription_bcp47_language_code VARCHAR(16) NOT NULL');
        $this->addSql('CREATE INDEX main_idx ON audio_transcription_web_vtts (audio_transcriptions_id, audio_transcription_bcp47_language_code)');
        $this->addSql('ALTER TABLE audio_transcription_happy_scribe_transcriptions CHANGE bcp47_language_code audio_transcription_bcp47_language_code VARCHAR(16) NOT NULL');
        $this->addSql('ALTER TABLE audio_transcription_happy_scribe_translation_tasks CHANGE bcp47_language_code audio_transcription_bcp47_language_code VARCHAR(16) NOT NULL');
        $this->addSql('DROP INDEX main_idx ON audio_transcription_suggested_summaries');
        $this->addSql('ALTER TABLE audio_transcription_suggested_summaries CHANGE bcp47_language_code audio_transcription_bcp47_language_code VARCHAR(16) NOT NULL');
        $this->addSql('CREATE INDEX main_idx ON audio_transcription_suggested_summaries (audio_transcriptions_id, audio_transcription_bcp47_language_code)');
        $this->addSql('DROP INDEX main_idx ON audio_transcription_words');
        $this->addSql('ALTER TABLE audio_transcription_words CHANGE bcp47_language_code audio_transcription_bcp47_language_code VARCHAR(16) NOT NULL');
        $this->addSql('CREATE INDEX main_idx ON audio_transcription_words (audio_transcriptions_id, audio_transcription_bcp47_language_code, speaker, data_start)');
    }
}
