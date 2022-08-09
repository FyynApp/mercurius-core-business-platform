<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220809134404 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE recording_settings_bags (client_id VARCHAR(32) NOT NULL, users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', settings LONGTEXT NOT NULL, INDEX IDX_E9EFF15767B3B43D (users_id), PRIMARY KEY(client_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE recording_settings_bags ADD CONSTRAINT FK_E9EFF15767B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE recording_settings');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE recording_settings (client_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, users_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', settings LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_4676F68A67B3B43D (users_id), PRIMARY KEY(client_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE recording_settings ADD CONSTRAINT FK_4676F68A67B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE recording_settings_bags');
    }
}
