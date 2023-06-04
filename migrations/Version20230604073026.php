<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230604073026 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audio_transcriptions ADD lingosync_processes_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE audio_transcriptions ADD CONSTRAINT FK_B02A10D12ABAD431 FOREIGN KEY (lingosync_processes_id) REFERENCES lingosync_processes (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B02A10D12ABAD431 ON audio_transcriptions (lingosync_processes_id)');
        $this->addSql('ALTER TABLE lingosync_process_tasks ADD number_of_times_handled INT UNSIGNED NOT NULL, ADD last_handled_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audio_transcriptions DROP FOREIGN KEY FK_B02A10D12ABAD431');
        $this->addSql('DROP INDEX UNIQ_B02A10D12ABAD431 ON audio_transcriptions');
        $this->addSql('ALTER TABLE audio_transcriptions DROP lingosync_processes_id');
        $this->addSql('ALTER TABLE lingosync_process_tasks DROP number_of_times_handled, DROP last_handled_at');
    }
}
