<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20221013161153
    extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE videos ADD video_only_presentationpage_template_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE videos ADD CONSTRAINT FK_29AA6432B86AE60E FOREIGN KEY (video_only_presentationpage_template_id) REFERENCES presentationpages (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_29AA6432B86AE60E ON videos (video_only_presentationpage_template_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE videos DROP FOREIGN KEY FK_29AA6432B86AE60E');
        $this->addSql('DROP INDEX IDX_29AA6432B86AE60E ON videos');
        $this->addSql('ALTER TABLE videos DROP video_only_presentationpage_template_id');
    }
}
