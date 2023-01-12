<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230111202243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE videos CHANGE has_asset_still_with_play_overlay_for_email has_asset_poster_still_with_play_overlay_for_email_png TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE videos CHANGE has_asset_poster_still_with_play_overlay_for_email_png has_asset_still_with_play_overlay_for_email TINYINT(1) NOT NULL');
    }
}
