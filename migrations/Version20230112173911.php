<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230112173911 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE video_mailings ADD improved_body_above_video TEXT NOT NULL, ADD improved_body_above_video_is_currently_being_generated TINYINT(1) NOT NULL, ADD improved_body_below_video TEXT NOT NULL, ADD improved_body_below_video_is_currently_being_generated TINYINT(1) NOT NULL, CHANGE body_above_video body_above_video TEXT NOT NULL, CHANGE body_below_video body_below_video TEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE video_mailings DROP improved_body_above_video, DROP improved_body_above_video_is_currently_being_generated, DROP improved_body_below_video, DROP improved_body_below_video_is_currently_being_generated, CHANGE body_above_video body_above_video VARCHAR(4096) NOT NULL, CHANGE body_below_video body_below_video VARCHAR(4096) NOT NULL');
    }
}
