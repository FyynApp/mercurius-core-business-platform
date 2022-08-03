<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220803174708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recording_settings ADD client_id TINYTEXT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4676F68A19EB6921 ON recording_settings (client_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_4676F68A19EB6921 ON recording_settings');
        $this->addSql('ALTER TABLE recording_settings DROP client_id');
    }
}
