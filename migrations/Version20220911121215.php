<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220911121215 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE presentationpages ADD background VARCHAR(64) NOT NULL');
        $this->addSql("UPDATE presentationpages SET background = 'bg-color'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE presentationpages DROP background');
    }
}
