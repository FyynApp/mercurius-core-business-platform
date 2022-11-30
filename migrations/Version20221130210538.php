<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221130210538 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE videos
            
            ADD asset_full_webm_width SMALLINT UNSIGNED DEFAULT NULL,
            ADD asset_full_webm_height SMALLINT UNSIGNED DEFAULT NULL,
            ADD asset_full_mp4_width SMALLINT UNSIGNED DEFAULT NULL,
            ADD asset_full_mp4_height SMALLINT UNSIGNED DEFAULT NULL,
            
            CHANGE asset_full_webm_fps asset_full_webm_fps DOUBLE PRECISION UNSIGNED DEFAULT NULL,
            CHANGE asset_full_webm_seconds asset_full_webm_seconds DOUBLE PRECISION UNSIGNED DEFAULT NULL,
            CHANGE asset_full_mp4_fps asset_full_mp4_fps DOUBLE PRECISION UNSIGNED DEFAULT NULL,
            CHANGE asset_full_mp4_seconds asset_full_mp4_seconds DOUBLE PRECISION UNSIGNED DEFAULT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE videos
            
            DROP asset_full_webm_width,
            DROP asset_full_webm_height,
            DROP asset_full_mp4_width, 
            DROP asset_full_mp4_height,
        
            CHANGE asset_full_webm_fps asset_full_webm_fps DOUBLE PRECISION DEFAULT NULL,
            CHANGE asset_full_webm_seconds asset_full_webm_seconds DOUBLE PRECISION DEFAULT NULL,
            CHANGE asset_full_mp4_fps asset_full_mp4_fps DOUBLE PRECISION DEFAULT NULL,
            CHANGE asset_full_mp4_seconds asset_full_mp4_seconds DOUBLE PRECISION DEFAULT NULL
        ');
    }
}
