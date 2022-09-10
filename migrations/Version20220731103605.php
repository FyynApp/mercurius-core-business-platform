<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220731103605 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE presentationpages DROP FOREIGN KEY FK_7BF635218FE1B5D0');

        $this->addSql('
            CREATE TABLE videos (
                id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                recording_sessions_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\',
                created_at DATETIME NOT NULL,
                has_asset_poster_still_webp TINYINT(1) NOT NULL,
                has_asset_poster_animated_webp TINYINT(1) NOT NULL,
                has_asset_poster_animated_gif TINYINT(1) NOT NULL,
                has_asset_full_webm TINYINT(1) NOT NULL,
                has_asset_full_mp4 TINYINT(1) NOT NULL,
                INDEX IDX_29AA643267B3B43D (users_id),
                UNIQUE INDEX UNIQ_29AA6432D98B1C16 (recording_sessions_id),
                INDEX created_at_idx (created_at),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ');

        $this->addSql('ALTER TABLE videos ADD CONSTRAINT FK_29AA643267B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE videos ADD CONSTRAINT FK_29AA6432D98B1C16 FOREIGN KEY (recording_sessions_id) REFERENCES recording_sessions (id) ON DELETE SET NULL');
        $this->addSql('DROP TABLE recording_session_full_videos');
        $this->addSql('ALTER TABLE presentationpages ADD CONSTRAINT FK_7BF635218FE1B5D0 FOREIGN KEY (recording_session_full_videos_id) REFERENCES videos (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE recording_sessions ADD is_done TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE presentationpages DROP FOREIGN KEY FK_7BF635218FE1B5D0');
        $this->addSql('CREATE TABLE recording_session_full_videos (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', recording_sessions_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', mime_type VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_8DFD21E9D98B1C16 (recording_sessions_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE recording_session_full_videos ADD CONSTRAINT FK_8DFD21E9D98B1C16 FOREIGN KEY (recording_sessions_id) REFERENCES recording_sessions (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE videos');
        $this->addSql('ALTER TABLE presentationpages DROP FOREIGN KEY FK_7BF635218FE1B5D0');
        $this->addSql('ALTER TABLE presentationpages ADD CONSTRAINT FK_7BF635218FE1B5D0 FOREIGN KEY (recording_session_full_videos_id) REFERENCES recording_session_full_videos (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE recording_sessions DROP is_done');
    }
}
