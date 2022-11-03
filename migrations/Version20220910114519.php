<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20220910114519
    extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE presentationpage_elements ADD element_horizontal_position VARCHAR(255) NOT NULL');
        $this->addSql("UPDATE presentationpage_elements SET element_horizontal_position = 'left'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE presentationpage_elements DROP element_horizontal_position');
    }
}
