<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220719161607 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE presentationpages (
                id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', 
                users_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\',
                recording_session_full_videos_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', 
                presentationpage_templates_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\',
                title VARCHAR(256) DEFAULT NULL,
                welcome_text VARCHAR(8192) DEFAULT NULL,
                INDEX IDX_7BF6352167B3B43D (users_id),
                INDEX IDX_7BF635218FE1B5D0 (recording_session_full_videos_id),
                INDEX IDX_7BF63521BE077F85 (presentationpage_templates_id),
                PRIMARY KEY(id)
            ) 
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            ;
        ');
        $this->addSql('ALTER TABLE presentationpages ADD CONSTRAINT FK_7BF6352167B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE;');
        $this->addSql('ALTER TABLE presentationpages ADD CONSTRAINT FK_7BF635218FE1B5D0 FOREIGN KEY (recording_session_full_videos_id) REFERENCES recording_session_full_videos (id) ON DELETE SET NULL;');
        $this->addSql('ALTER TABLE presentationpages ADD CONSTRAINT FK_7BF63521BE077F85 FOREIGN KEY (presentationpage_templates_id) REFERENCES presentationpage_templates (id) ON DELETE SET NULL;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE presentationpages');
    }
}
