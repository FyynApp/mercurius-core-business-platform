<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230306125536 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE video_player_session_events DROP name');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE video_player_session_events ADD name VARCHAR(256) NOT NULL');
    }
}
