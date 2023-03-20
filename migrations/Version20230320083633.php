<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230320083633 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recording_sessions ADD short_id VARCHAR(12) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3953CB5CF8496E51 ON recording_sessions (short_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_3953CB5CF8496E51 ON recording_sessions');
        $this->addSql('ALTER TABLE recording_sessions DROP short_id');
    }
}
