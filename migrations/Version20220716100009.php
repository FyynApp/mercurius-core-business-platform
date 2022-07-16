<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220716100009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE presentationpage_templates 
            (
                id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\',
                users_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\',
                title VARCHAR(256) DEFAULT NULL,
                INDEX IDX_4A5A7C867B3B43D (users_id),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
            ;'
        );
        $this->addSql('ALTER TABLE presentationpage_templates ADD CONSTRAINT FK_4A5A7C867B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE presentationpage_templates');
    }
}
