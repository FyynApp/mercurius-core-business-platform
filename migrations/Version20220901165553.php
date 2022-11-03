<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20220901165553
    extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE presentationpages ADD type VARCHAR(255) NOT NULL, DROP is_template');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE presentationpages ADD is_template TINYINT(1) NOT NULL, DROP type');
    }
}
