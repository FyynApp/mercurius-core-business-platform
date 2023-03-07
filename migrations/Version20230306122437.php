<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230306122437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE video_player_session_events
            (
                id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                video_player_sessions_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                name VARCHAR(256) NOT NULL,
                created_at DATETIME NOT NULL,
                `current_time` DOUBLE PRECISION UNSIGNED NOT NULL,
                INDEX IDX_A08CD5D9581C3854 (video_player_sessions_id),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE video_player_sessions 
            (
                id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                videos_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                ip_address VARCHAR(256) NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX IDX_71298D99763C10B2 (videos_id),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ');

        $this->addSql('ALTER TABLE video_player_session_events ADD CONSTRAINT FK_A08CD5D9581C3854 FOREIGN KEY (video_player_sessions_id) REFERENCES video_player_sessions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE video_player_sessions ADD CONSTRAINT FK_71298D99763C10B2 FOREIGN KEY (videos_id) REFERENCES videos (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE video_player_session_events DROP FOREIGN KEY FK_A08CD5D9581C3854');
        $this->addSql('ALTER TABLE video_player_sessions DROP FOREIGN KEY FK_71298D99763C10B2');
        $this->addSql('DROP TABLE video_player_session_events');
        $this->addSql('DROP TABLE video_player_sessions');
    }
}
