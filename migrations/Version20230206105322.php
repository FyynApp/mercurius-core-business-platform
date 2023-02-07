<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230206105322 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE videos
            ADD has_asset_original TINYINT(1) NOT NULL,
            ADD asset_original_fps DOUBLE PRECISION UNSIGNED DEFAULT NULL,
            ADD asset_original_seconds DOUBLE PRECISION UNSIGNED DEFAULT NULL,
            ADD asset_original_width SMALLINT UNSIGNED DEFAULT NULL,
            ADD asset_original_height SMALLINT UNSIGNED DEFAULT NULL,
            ADD asset_original_mime_type VARCHAR(32) DEFAULT NULL
        ');
        $this->addSql('
            UPDATE videos
            SET
                has_asset_original       = has_asset_full_webm,
                asset_original_fps       = asset_full_webm_fps,
                asset_original_seconds   = asset_full_webm_seconds,
                asset_original_width     = asset_full_webm_width,
                asset_original_height    = asset_full_webm_height,
                asset_original_fps       = asset_full_webm_fps,
                asset_original_mime_type = "video/webm"
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE videos DROP has_asset_original, DROP asset_original_fps, DROP asset_original_seconds, DROP asset_original_width, DROP asset_original_height, DROP asset_original_mime_type');
    }
}
