<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20220921064038
    extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE rememberme_token (series VARCHAR(88) NOT NULL, value VARCHAR(88) NOT NULL, lastUsed DATETIME NOT NULL, class VARCHAR(100) NOT NULL, username VARCHAR(200) NOT NULL, PRIMARY KEY(series)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("UPDATE users SET roles = '[\"ROLE_REGISTERED_USER\"]' WHERE roles = '[]'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE rememberme_token');
        $this->addSql("UPDATE users SET roles = '[]' WHERE roles = '[\"ROLE_REGISTERED_USER\"]'");
    }
}
