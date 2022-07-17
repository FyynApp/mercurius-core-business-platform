<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220717102119 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE presentationpage_templates ADD bg_color VARCHAR(7) NOT NULL, ADD text_color VARCHAR(7) NOT NULL;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE presentationpage_templates DROP bg_color, DROP text_color;');
    }
}
