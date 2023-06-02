<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230602142304 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE videos ADD created_by_lingosync_process_tasks_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE videos ADD CONSTRAINT FK_29AA6432726AEC71 FOREIGN KEY (created_by_lingosync_process_tasks_id) REFERENCES lingosync_process_tasks (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_29AA6432726AEC71 ON videos (created_by_lingosync_process_tasks_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE videos DROP FOREIGN KEY FK_29AA6432726AEC71');
        $this->addSql('DROP INDEX UNIQ_29AA6432726AEC71 ON videos');
        $this->addSql('ALTER TABLE videos DROP created_by_lingosync_process_tasks_id');
    }
}
