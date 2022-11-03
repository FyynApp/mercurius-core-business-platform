<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20221014171738
    extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE videos ADD short_id VARCHAR(8) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_29AA6432F8496E51 ON videos (short_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_29AA6432F8496E51 ON videos');
        $this->addSql('ALTER TABLE videos DROP short_id');
    }
}
