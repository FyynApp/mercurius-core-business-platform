<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230131093358 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE custom_domain_settings ADD http_setup_status SMALLINT NOT NULL, CHANGE check_status dns_setup_status SMALLINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE custom_domain_settings ADD check_status SMALLINT NOT NULL, DROP dns_setup_status, DROP http_setup_status');
    }
}
