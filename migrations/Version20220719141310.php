<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220719141310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recording_sessions ADD created_at DATETIME NOT NULL;');
        $this->addSql('CREATE INDEX created_at_idx ON recording_sessions (created_at);');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX created_at_idx ON recording_sessions;');
        $this->addSql('ALTER TABLE recording_sessions DROP created_at;');
    }
}
