<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230214064524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE videos
            ADD main_cta_text VARCHAR(1024) DEFAULT NULL,
            ADD main_cta_label VARCHAR(1024) DEFAULT NULL,
            ADD main_cta_url VARCHAR(1024) DEFAULT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE videos DROP main_cta_text, DROP main_cta_label, DROP main_cta_url');
    }
}
