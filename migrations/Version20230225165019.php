<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230225165019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM settings_logo_uploads');
        $this->addSql('ALTER TABLE settings_logo_uploads DROP FOREIGN KEY FK_F68A37EE67B3B43D');
        $this->addSql('DROP INDEX IDX_F68A37EE67B3B43D ON settings_logo_uploads');
        $this->addSql('ALTER TABLE settings_logo_uploads CHANGE users_id organizations_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE settings_logo_uploads ADD CONSTRAINT FK_F68A37EE86288A55 FOREIGN KEY (organizations_id) REFERENCES organizations (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_F68A37EE86288A55 ON settings_logo_uploads (organizations_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settings_logo_uploads DROP FOREIGN KEY FK_F68A37EE86288A55');
        $this->addSql('DROP INDEX IDX_F68A37EE86288A55 ON settings_logo_uploads');
        $this->addSql('ALTER TABLE settings_logo_uploads CHANGE organizations_id users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE settings_logo_uploads ADD CONSTRAINT FK_F68A37EE67B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_F68A37EE67B3B43D ON settings_logo_uploads (users_id)');
    }
}
