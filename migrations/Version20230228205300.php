<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230228205300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recording_sessions DROP FOREIGN KEY FK_3953CB5C67B3B43D');
        $this->addSql('ALTER TABLE recording_sessions ADD organizations_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', CHANGE users_id users_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE recording_sessions ADD CONSTRAINT FK_3953CB5C86288A55 FOREIGN KEY (organizations_id) REFERENCES organizations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recording_sessions ADD CONSTRAINT FK_3953CB5C67B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_3953CB5C86288A55 ON recording_sessions (organizations_id)');
        $this->addSql('ALTER TABLE videos DROP FOREIGN KEY FK_29AA643267B3B43D');
        $this->addSql('ALTER TABLE videos ADD organizations_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', CHANGE users_id users_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE videos ADD CONSTRAINT FK_29AA643286288A55 FOREIGN KEY (organizations_id) REFERENCES organizations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE videos ADD CONSTRAINT FK_29AA643267B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_29AA643286288A55 ON videos (organizations_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE videos DROP FOREIGN KEY FK_29AA643286288A55');
        $this->addSql('ALTER TABLE videos DROP FOREIGN KEY FK_29AA643267B3B43D');
        $this->addSql('DROP INDEX IDX_29AA643286288A55 ON videos');
        $this->addSql('ALTER TABLE videos DROP organizations_id, CHANGE users_id users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE videos ADD CONSTRAINT FK_29AA643267B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recording_sessions DROP FOREIGN KEY FK_3953CB5C86288A55');
        $this->addSql('ALTER TABLE recording_sessions DROP FOREIGN KEY FK_3953CB5C67B3B43D');
        $this->addSql('DROP INDEX IDX_3953CB5C86288A55 ON recording_sessions');
        $this->addSql('ALTER TABLE recording_sessions DROP organizations_id, CHANGE users_id users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE recording_sessions ADD CONSTRAINT FK_3953CB5C67B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
    }
}
