<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220713110113 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE thirdpartyauth_linkedin_resourceowners ADD users_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\';');
        $this->addSql('ALTER TABLE thirdpartyauth_linkedin_resourceowners ADD CONSTRAINT FK_2F1E99A967B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE SET NULL;');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2F1E99A967B3B43D ON thirdpartyauth_linkedin_resourceowners (users_id);');
        $this->addSql('DROP INDEX uniq_2f1e99a97a9b7c2e ON thirdpartyauth_linkedin_resourceowners;');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2F1E99A9A6883514 ON thirdpartyauth_linkedin_resourceowners (sorted_profile_picture_800_url);');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE thirdpartyauth_linkedin_resourceowners DROP FOREIGN KEY FK_2F1E99A967B3B43D;');
        $this->addSql('DROP INDEX UNIQ_2F1E99A967B3B43D ON thirdpartyauth_linkedin_resourceowners;');
        $this->addSql('ALTER TABLE thirdpartyauth_linkedin_resourceowners DROP users_id;');
        $this->addSql('DROP INDEX uniq_2f1e99a9a6883514 ON thirdpartyauth_linkedin_resourceowners;');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2F1E99A97A9B7C2E ON thirdpartyauth_linkedin_resourceowners (sorted_profile_picture_800_url);');
    }
}
