<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230309174018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE FULLTEXT INDEX title_fulltext_idx ON videos (title)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX title_fulltext_idx ON videos');
    }
}
