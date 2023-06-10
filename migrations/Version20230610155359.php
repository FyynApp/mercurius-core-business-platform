<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230610155359 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE videos ADD internally_created_source_file_path VARCHAR(1024) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_29AA6432197B4177 ON videos (internally_created_source_file_path)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_29AA6432197B4177 ON videos');
        $this->addSql('ALTER TABLE videos DROP internally_created_source_file_path');
    }
}
