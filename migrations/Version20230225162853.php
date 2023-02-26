<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230225162853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM custom_domain_settings;');
        $this->addSql('ALTER TABLE custom_domain_settings DROP FOREIGN KEY FK_90E7317567B3B43D');
        $this->addSql('DROP INDEX UNIQ_90E7317567B3B43D ON custom_domain_settings');
        $this->addSql('ALTER TABLE custom_domain_settings CHANGE users_id organizations_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE custom_domain_settings ADD CONSTRAINT FK_90E7317586288A55 FOREIGN KEY (organizations_id) REFERENCES organizations (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_90E7317586288A55 ON custom_domain_settings (organizations_id)');

        $this->addSql('DELETE FROM custom_logo_settings;');
        $this->addSql('ALTER TABLE custom_logo_settings DROP FOREIGN KEY FK_3F6AB5E867B3B43D');
        $this->addSql('DROP INDEX UNIQ_3F6AB5E867B3B43D ON custom_logo_settings');
        $this->addSql('ALTER TABLE custom_logo_settings CHANGE users_id organizations_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE custom_logo_settings ADD CONSTRAINT FK_3F6AB5E886288A55 FOREIGN KEY (organizations_id) REFERENCES organizations (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3F6AB5E886288A55 ON custom_logo_settings (organizations_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE custom_logo_settings DROP FOREIGN KEY FK_3F6AB5E886288A55');
        $this->addSql('DROP INDEX UNIQ_3F6AB5E886288A55 ON custom_logo_settings');
        $this->addSql('ALTER TABLE custom_logo_settings CHANGE organizations_id users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE custom_logo_settings ADD CONSTRAINT FK_3F6AB5E867B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3F6AB5E867B3B43D ON custom_logo_settings (users_id)');
        $this->addSql('ALTER TABLE custom_domain_settings DROP FOREIGN KEY FK_90E7317586288A55');
        $this->addSql('DROP INDEX UNIQ_90E7317586288A55 ON custom_domain_settings');
        $this->addSql('ALTER TABLE custom_domain_settings CHANGE organizations_id users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE custom_domain_settings ADD CONSTRAINT FK_90E7317567B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_90E7317567B3B43D ON custom_domain_settings (users_id)');
    }
}
