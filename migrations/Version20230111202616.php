<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230111202616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE videos ADD asset_poster_still_with_play_overlay_for_email_png_width SMALLINT UNSIGNED DEFAULT NULL, ADD asset_poster_still_with_play_overlay_for_email_png_height SMALLINT UNSIGNED DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE videos DROP asset_poster_still_with_play_overlay_for_email_png_width, DROP asset_poster_still_with_play_overlay_for_email_png_height');
    }
}
