<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220911121901 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE presentationpages ADD fg_color VARCHAR(7) NOT NULL');
        $this->addSql("UPDATE presentationpages SET fg_color = '#37474f'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE presentationpages DROP fg_color');
    }
}
