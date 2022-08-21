<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220821104913 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE presentationpage_template_elements (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', presentationpage_templates_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', element_type VARCHAR(255) DEFAULT NULL, position INT UNSIGNED NOT NULL, INDEX IDX_B791E115BE077F85 (presentationpage_templates_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE presentationpage_template_elements ADD CONSTRAINT FK_B791E115BE077F85 FOREIGN KEY (presentationpage_templates_id) REFERENCES presentationpage_templates (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE presentationpage_template_elements DROP FOREIGN KEY FK_B791E115BE077F85');
        $this->addSql('DROP TABLE presentationpage_template_elements');
    }
}
