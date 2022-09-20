<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220920180541 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE unregistered_clients (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', email VARCHAR(180) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE recording_sessions ADD unregistered_clients_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', CHANGE users_id users_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE recording_sessions ADD CONSTRAINT FK_3953CB5C4DC87446 FOREIGN KEY (unregistered_clients_id) REFERENCES unregistered_clients (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_3953CB5C4DC87446 ON recording_sessions (unregistered_clients_id)');
        $this->addSql('ALTER TABLE recording_settings_bags ADD unregistered_clients_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', CHANGE users_id users_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE recording_settings_bags ADD CONSTRAINT FK_E9EFF1574DC87446 FOREIGN KEY (unregistered_clients_id) REFERENCES unregistered_clients (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_E9EFF1574DC87446 ON recording_settings_bags (unregistered_clients_id)');
        $this->addSql('ALTER TABLE videos ADD unregistered_clients_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', CHANGE users_id users_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE videos ADD CONSTRAINT FK_29AA64324DC87446 FOREIGN KEY (unregistered_clients_id) REFERENCES unregistered_clients (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_29AA64324DC87446 ON videos (unregistered_clients_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recording_sessions DROP FOREIGN KEY FK_3953CB5C4DC87446');
        $this->addSql('ALTER TABLE recording_settings_bags DROP FOREIGN KEY FK_E9EFF1574DC87446');
        $this->addSql('ALTER TABLE videos DROP FOREIGN KEY FK_29AA64324DC87446');
        $this->addSql('DROP TABLE unregistered_clients');
        $this->addSql('DROP INDEX IDX_29AA64324DC87446 ON videos');
        $this->addSql('ALTER TABLE videos DROP unregistered_clients_id, CHANGE users_id users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('DROP INDEX IDX_E9EFF1574DC87446 ON recording_settings_bags');
        $this->addSql('ALTER TABLE recording_settings_bags DROP unregistered_clients_id, CHANGE users_id users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('DROP INDEX IDX_3953CB5C4DC87446 ON recording_sessions');
        $this->addSql('ALTER TABLE recording_sessions DROP unregistered_clients_id, CHANGE users_id users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
    }
}
