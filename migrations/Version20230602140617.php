<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230602140617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE lingosync_process_tasks
            (
                id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                lingosync_processes_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                created_at DATETIME NOT NULL,
                target_language_bcp47_language_code VARCHAR(16) DEFAULT NULL,
                task_type VARCHAR(48) NOT NULL,
                task_status VARCHAR(16) NOT NULL,
                expected_number_of_steps INT UNSIGNED NOT NULL,
                finished_number_of_steps INT UNSIGNED NOT NULL,
                INDEX IDX_DBE638C52ABAD431 (lingosync_processes_id),
                INDEX created_at_idx (created_at),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE lingosync_processes
            (
                id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                videos_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                created_at DATETIME NOT NULL,
                original_language_bcp47_language_code VARCHAR(16) NOT NULL,
                INDEX IDX_74E3767763C10B2 (videos_id),
                INDEX created_at_idx (created_at),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ');

        $this->addSql('ALTER TABLE lingosync_process_tasks ADD CONSTRAINT FK_DBE638C52ABAD431 FOREIGN KEY (lingosync_processes_id) REFERENCES lingosync_processes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lingosync_processes ADD CONSTRAINT FK_74E3767763C10B2 FOREIGN KEY (videos_id) REFERENCES videos (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lingosync_process_tasks DROP FOREIGN KEY FK_DBE638C52ABAD431');
        $this->addSql('ALTER TABLE lingosync_processes DROP FOREIGN KEY FK_74E3767763C10B2');
        $this->addSql('DROP TABLE lingosync_process_tasks');
        $this->addSql('DROP TABLE lingosync_processes');
    }
}
