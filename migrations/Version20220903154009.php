<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220903154009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE presentationpages ADD draft_of_presentationpages_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD is_draft TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE presentationpages ADD CONSTRAINT FK_7BF63521BD688254 FOREIGN KEY (draft_of_presentationpages_id) REFERENCES presentationpages (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_7BF63521BD688254 ON presentationpages (draft_of_presentationpages_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE presentationpages DROP FOREIGN KEY FK_7BF63521BD688254');
        $this->addSql('DROP INDEX IDX_7BF63521BD688254 ON presentationpages');
        $this->addSql('ALTER TABLE presentationpages DROP draft_of_presentationpages_id, DROP created_at, DROP updated_at, DROP is_draft');
    }
}
