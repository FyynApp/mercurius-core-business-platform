<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220712130915 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD is_verified TINYINT(1) NOT NULL;');
        $this->addSql('DROP INDEX uniq_8d93d649e7927c74 ON users;');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email);');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP is_verified;');
        $this->addSql('DROP INDEX uniq_1483a5e9e7927c74 ON users;');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON users (email);');
    }
}
