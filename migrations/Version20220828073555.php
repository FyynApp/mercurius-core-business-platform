<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220828073555 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DELETE FROM presentationpages');
        $this->addSql('ALTER TABLE presentationpages DROP FOREIGN KEY FK_7BF63521BE077F85');
        $this->addSql('CREATE TABLE presentationpage_elements (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', presentationpages_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', element_variant VARCHAR(255) NOT NULL, position INT UNSIGNED NOT NULL, text_content TEXT DEFAULT NULL, INDEX IDX_A2C1B50DA9217C03 (presentationpages_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE presentationpage_elements ADD CONSTRAINT FK_A2C1B50DA9217C03 FOREIGN KEY (presentationpages_id) REFERENCES presentationpages (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE presentationpage_template_elements DROP FOREIGN KEY FK_B791E115BE077F85');
        $this->addSql('ALTER TABLE presentationpage_templates DROP FOREIGN KEY FK_4A5A7C867B3B43D');
        $this->addSql('DROP TABLE presentationpage_template_elements');
        $this->addSql('DROP TABLE presentationpage_templates');
        $this->addSql('DROP INDEX IDX_7BF63521BE077F85 ON presentationpages');
        $this->addSql('ALTER TABLE presentationpages ADD is_template TINYINT(1) NOT NULL, ADD bg_color VARCHAR(7) NOT NULL, ADD text_color VARCHAR(7) NOT NULL, DROP presentationpage_templates_id, DROP welcome_text, DROP calendly_embed_code');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE presentationpage_template_elements (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', presentationpage_templates_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', element_variant VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, position INT UNSIGNED NOT NULL, text_content TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_B791E115BE077F85 (presentationpage_templates_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE presentationpage_templates (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', users_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', title VARCHAR(256) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, bg_color VARCHAR(7) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, text_color VARCHAR(7) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_4A5A7C867B3B43D (users_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE presentationpage_template_elements ADD CONSTRAINT FK_B791E115BE077F85 FOREIGN KEY (presentationpage_templates_id) REFERENCES presentationpage_templates (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE presentationpage_templates ADD CONSTRAINT FK_4A5A7C867B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE presentationpage_elements DROP FOREIGN KEY FK_A2C1B50DA9217C03');
        $this->addSql('DROP TABLE presentationpage_elements');
        $this->addSql('ALTER TABLE presentationpages ADD presentationpage_templates_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', ADD welcome_text VARCHAR(8192) DEFAULT NULL, ADD calendly_embed_code VARCHAR(2048) DEFAULT NULL, DROP is_template, DROP bg_color, DROP text_color');
        $this->addSql('ALTER TABLE presentationpages ADD CONSTRAINT FK_7BF63521BE077F85 FOREIGN KEY (presentationpage_templates_id) REFERENCES presentationpage_templates (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_7BF63521BE077F85 ON presentationpages (presentationpage_templates_id)');
    }
}
