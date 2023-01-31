<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230131080521 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE videos DROP FOREIGN KEY FK_29AA643236C46A13');
        $this->addSql('DROP INDEX uniq_29aa643236c46a13 ON videos');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_29AA64324ADB3485 ON videos (recordings_video_uploads_id)');
        $this->addSql('ALTER TABLE videos ADD CONSTRAINT FK_29AA643236C46A13 FOREIGN KEY (recordings_video_uploads_id) REFERENCES recordings_video_uploads (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE videos DROP FOREIGN KEY FK_29AA64324ADB3485');
        $this->addSql('DROP INDEX uniq_29aa64324adb3485 ON videos');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_29AA643236C46A13 ON videos (recordings_video_uploads_id)');
        $this->addSql('ALTER TABLE videos ADD CONSTRAINT FK_29AA64324ADB3485 FOREIGN KEY (recordings_video_uploads_id) REFERENCES recordings_video_uploads (id) ON DELETE SET NULL');
    }
}
