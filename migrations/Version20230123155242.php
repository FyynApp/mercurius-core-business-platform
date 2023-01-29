<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230123155242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tus_uploads DROP FOREIGN KEY FK_7B49605D67B3B43D');
        $this->addSql('DROP INDEX IDX_7B49605D67B3B43D ON tus_uploads');
        $this->addSql('ALTER TABLE tus_uploads DROP users_id');
        $this->addSql('ALTER TABLE videos ADD tus_uploads_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE videos ADD CONSTRAINT FK_29AA643268D8EA3B FOREIGN KEY (tus_uploads_id) REFERENCES tus_uploads (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_29AA643268D8EA3B ON videos (tus_uploads_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE videos DROP FOREIGN KEY FK_29AA643268D8EA3B');
        $this->addSql('DROP INDEX UNIQ_29AA643268D8EA3B ON videos');
        $this->addSql('ALTER TABLE videos DROP tus_uploads_id');
        $this->addSql('ALTER TABLE tus_uploads ADD users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE tus_uploads ADD CONSTRAINT FK_7B49605D67B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_7B49605D67B3B43D ON tus_uploads (users_id)');
    }
}
