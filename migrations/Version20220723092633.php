<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220723092633 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE presentationpage_templates (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', title VARCHAR(256) DEFAULT NULL, bg_color VARCHAR(7) NOT NULL, text_color VARCHAR(7) NOT NULL, INDEX IDX_4A5A7C867B3B43D (users_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE presentationpages (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', recording_session_full_videos_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', presentationpage_templates_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', title VARCHAR(256) DEFAULT NULL, welcome_text VARCHAR(8192) DEFAULT NULL, INDEX IDX_7BF6352167B3B43D (users_id), INDEX IDX_7BF635218FE1B5D0 (recording_session_full_videos_id), INDEX IDX_7BF63521BE077F85 (presentationpage_templates_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recording_session_full_videos (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', recording_sessions_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', mime_type VARCHAR(32) NOT NULL, UNIQUE INDEX UNIQ_8DFD21E9D98B1C16 (recording_sessions_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recording_session_video_chunks (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', recording_sessions_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', name VARCHAR(256) NOT NULL, mime_type VARCHAR(32) NOT NULL, INDEX IDX_BD96610DD98B1C16 (recording_sessions_id), UNIQUE INDEX session_name (recording_sessions_id, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recording_sessions (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', created_at DATETIME NOT NULL, INDEX IDX_3953CB5C67B3B43D (users_id), INDEX created_at_idx (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE thirdpartyauth_linkedin_resourceowners (id VARCHAR(255) NOT NULL, users_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', email VARCHAR(180) NOT NULL, first_name VARCHAR(256) DEFAULT NULL, last_name VARCHAR(256) DEFAULT NULL, sorted_profile_picture_800_url VARCHAR(2048) DEFAULT NULL, sorted_profile_picture_800_content_type VARCHAR(100) DEFAULT NULL, UNIQUE INDEX UNIQ_2F1E99A9E7927C74 (email), UNIQUE INDEX UNIQ_2F1E99A9A6883514 (sorted_profile_picture_800_url), UNIQUE INDEX UNIQ_2F1E99A967B3B43D (users_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', email VARCHAR(180) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, is_verified TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE presentationpage_templates ADD CONSTRAINT FK_4A5A7C867B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE presentationpages ADD CONSTRAINT FK_7BF6352167B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE presentationpages ADD CONSTRAINT FK_7BF635218FE1B5D0 FOREIGN KEY (recording_session_full_videos_id) REFERENCES recording_session_full_videos (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE presentationpages ADD CONSTRAINT FK_7BF63521BE077F85 FOREIGN KEY (presentationpage_templates_id) REFERENCES presentationpage_templates (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE recording_session_full_videos ADD CONSTRAINT FK_8DFD21E9D98B1C16 FOREIGN KEY (recording_sessions_id) REFERENCES recording_sessions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recording_session_video_chunks ADD CONSTRAINT FK_BD96610DD98B1C16 FOREIGN KEY (recording_sessions_id) REFERENCES recording_sessions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recording_sessions ADD CONSTRAINT FK_3953CB5C67B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE thirdpartyauth_linkedin_resourceowners ADD CONSTRAINT FK_2F1E99A967B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE presentationpages DROP FOREIGN KEY FK_7BF63521BE077F85');
        $this->addSql('ALTER TABLE presentationpages DROP FOREIGN KEY FK_7BF635218FE1B5D0');
        $this->addSql('ALTER TABLE recording_session_full_videos DROP FOREIGN KEY FK_8DFD21E9D98B1C16');
        $this->addSql('ALTER TABLE recording_session_video_chunks DROP FOREIGN KEY FK_BD96610DD98B1C16');
        $this->addSql('ALTER TABLE presentationpage_templates DROP FOREIGN KEY FK_4A5A7C867B3B43D');
        $this->addSql('ALTER TABLE presentationpages DROP FOREIGN KEY FK_7BF6352167B3B43D');
        $this->addSql('ALTER TABLE recording_sessions DROP FOREIGN KEY FK_3953CB5C67B3B43D');
        $this->addSql('ALTER TABLE thirdpartyauth_linkedin_resourceowners DROP FOREIGN KEY FK_2F1E99A967B3B43D');
        $this->addSql('DROP TABLE presentationpage_templates');
        $this->addSql('DROP TABLE presentationpages');
        $this->addSql('DROP TABLE recording_session_full_videos');
        $this->addSql('DROP TABLE recording_session_video_chunks');
        $this->addSql('DROP TABLE recording_sessions');
        $this->addSql('DROP TABLE thirdpartyauth_linkedin_resourceowners');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
