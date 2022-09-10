<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220809130641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE recording_settings');
        $this->addSql('CREATE TABLE recording_settings (client_id VARCHAR(32) NOT NULL, users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', settings LONGTEXT NOT NULL, INDEX IDX_4676F68A67B3B43D (users_id), PRIMARY KEY(client_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE recording_settings ADD CONSTRAINT FK_4676F68A67B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE recording_settings');
    }
}
