<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220731130322 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE presentationpages DROP FOREIGN KEY FK_7BF635218FE1B5D0');
        $this->addSql('DROP INDEX IDX_7BF635218FE1B5D0 ON presentationpages');
        $this->addSql('ALTER TABLE presentationpages CHANGE recording_session_full_videos_id videos_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE presentationpages ADD CONSTRAINT FK_7BF63521763C10B2 FOREIGN KEY (videos_id) REFERENCES videos (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_7BF63521763C10B2 ON presentationpages (videos_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE presentationpages DROP FOREIGN KEY FK_7BF63521763C10B2');
        $this->addSql('DROP INDEX IDX_7BF63521763C10B2 ON presentationpages');
        $this->addSql('ALTER TABLE presentationpages CHANGE videos_id recording_session_full_videos_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE presentationpages ADD CONSTRAINT FK_7BF635218FE1B5D0 FOREIGN KEY (recording_session_full_videos_id) REFERENCES videos (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_7BF635218FE1B5D0 ON presentationpages (recording_session_full_videos_id)');
    }
}
