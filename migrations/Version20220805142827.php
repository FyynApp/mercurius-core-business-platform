<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220805142827 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE videos ADD asset_full_webm_fps DOUBLE PRECISION DEFAULT NULL, ADD asset_full_webm_seconds DOUBLE PRECISION DEFAULT NULL, ADD asset_full_mp4_fps DOUBLE PRECISION DEFAULT NULL, ADD asset_full_mp4_seconds DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE videos DROP asset_full_webm_fps, DROP asset_full_webm_seconds, DROP asset_full_mp4_fps, DROP asset_full_mp4_seconds');
    }
}
