<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230129134609 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE videos DROP FOREIGN KEY FK_29AA643268D8EA3B');
        $this->addSql('CREATE TABLE custom_logo_settings (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', logo_uploads_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', created_at DATETIME NOT NULL, INDEX IDX_3F6AB5E867B3B43D (users_id), UNIQUE INDEX UNIQ_3F6AB5E89DB8C871 (logo_uploads_id), INDEX created_at_idx (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('
            CREATE TABLE recordings_video_uploads
            (
                id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                tus_token CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                file_name VARCHAR(256) NOT NULL,
                file_type VARCHAR(32) NOT NULL,
                created_at DATETIME NOT NULL,
                UNIQUE INDEX UNIQ_6161774516BB3F31 (tus_token),
                INDEX created_at_idx (created_at),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4 
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE settings_logo_uploads (
                id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                custom_logo_settings_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\',
                tus_token CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                file_name VARCHAR(256) NOT NULL,
                file_type VARCHAR(32) NOT NULL,
                created_at DATETIME NOT NULL,
                UNIQUE INDEX UNIQ_F68A37EE16BB3F31 (tus_token),
                INDEX IDX_F68A37EE67B3B43D (users_id),
                UNIQUE INDEX UNIQ_F68A37EEF56596AC (custom_logo_settings_id),
                INDEX created_at_idx (created_at),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4 
            COLLATE `utf8mb4_unicode_ci` 
            ENGINE = InnoDB
        ');

        $this->addSql('ALTER TABLE custom_logo_settings ADD CONSTRAINT FK_3F6AB5E867B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE custom_logo_settings ADD CONSTRAINT FK_3F6AB5E89DB8C871 FOREIGN KEY (logo_uploads_id) REFERENCES settings_logo_uploads (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE settings_logo_uploads ADD CONSTRAINT FK_F68A37EE67B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE settings_logo_uploads ADD CONSTRAINT FK_F68A37EEF56596AC FOREIGN KEY (custom_logo_settings_id) REFERENCES custom_logo_settings (id) ON DELETE SET NULL');
        $this->addSql('DROP TABLE tus_uploads');
        $this->addSql('DROP INDEX UNIQ_29AA643268D8EA3B ON videos');
        $this->addSql('ALTER TABLE videos CHANGE tus_uploads_id video_uploads_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE videos ADD CONSTRAINT FK_29AA643236C46A13 FOREIGN KEY (video_uploads_id) REFERENCES recordings_video_uploads (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_29AA643236C46A13 ON videos (video_uploads_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE videos DROP FOREIGN KEY FK_29AA643236C46A13');
        $this->addSql('CREATE TABLE tus_uploads (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', token CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', file_name VARCHAR(256) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, file_type VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, INDEX created_at_idx (created_at), UNIQUE INDEX UNIQ_7B49605D5F37A13B (token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE custom_logo_settings DROP FOREIGN KEY FK_3F6AB5E867B3B43D');
        $this->addSql('ALTER TABLE custom_logo_settings DROP FOREIGN KEY FK_3F6AB5E89DB8C871');
        $this->addSql('ALTER TABLE settings_logo_uploads DROP FOREIGN KEY FK_F68A37EE67B3B43D');
        $this->addSql('ALTER TABLE settings_logo_uploads DROP FOREIGN KEY FK_F68A37EEF56596AC');
        $this->addSql('DROP TABLE custom_logo_settings');
        $this->addSql('DROP TABLE recordings_video_uploads');
        $this->addSql('DROP TABLE settings_logo_uploads');
        $this->addSql('DROP INDEX UNIQ_29AA643236C46A13 ON videos');
        $this->addSql('ALTER TABLE videos CHANGE video_uploads_id tus_uploads_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE videos ADD CONSTRAINT FK_29AA643268D8EA3B FOREIGN KEY (tus_uploads_id) REFERENCES tus_uploads (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_29AA643268D8EA3B ON videos (tus_uploads_id)');
    }
}
