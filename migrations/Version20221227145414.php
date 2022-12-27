<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221227145414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE videos
            ADD asset_poster_still_webp_width SMALLINT UNSIGNED DEFAULT NULL,
            ADD asset_poster_still_webp_height SMALLINT UNSIGNED DEFAULT NULL,
            ADD asset_poster_animated_webp_width SMALLINT UNSIGNED DEFAULT NULL,
            ADD asset_poster_animated_webp_height SMALLINT UNSIGNED DEFAULT NULL
        ;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE videos
            DROP asset_poster_still_webp_width,
            DROP asset_poster_still_webp_height,
            DROP asset_poster_animated_webp_width,
            DROP asset_poster_animated_webp_height
        ;');
    }
}
