<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Feature\Presentationpages\PresentationpageCategory;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221010203059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE presentationpages ADD category VARCHAR(255) NOT NULL');
        $this->addSql('UPDATE presentationpages SET category = "' . PresentationpageCategory::Default->value . '"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE presentationpages DROP category');
    }
}
