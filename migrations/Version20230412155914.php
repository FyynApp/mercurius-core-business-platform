<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230412155914 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE process_log_entries ADD users_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', ADD organizations_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE process_log_entries ADD CONSTRAINT FK_1B40484367B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE process_log_entries ADD CONSTRAINT FK_1B40484386288A55 FOREIGN KEY (organizations_id) REFERENCES organizations (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_1B40484367B3B43D ON process_log_entries (users_id)');
        $this->addSql('CREATE INDEX IDX_1B40484386288A55 ON process_log_entries (organizations_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE process_log_entries DROP FOREIGN KEY FK_1B40484367B3B43D');
        $this->addSql('ALTER TABLE process_log_entries DROP FOREIGN KEY FK_1B40484386288A55');
        $this->addSql('DROP INDEX IDX_1B40484367B3B43D ON process_log_entries');
        $this->addSql('DROP INDEX IDX_1B40484386288A55 ON process_log_entries');
        $this->addSql('ALTER TABLE process_log_entries DROP users_id, DROP organizations_id');
    }
}
