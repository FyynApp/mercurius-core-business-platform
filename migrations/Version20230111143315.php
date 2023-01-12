<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230111143315 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE video_mailings
            ADD receiver_mail_address VARCHAR(256) NOT NULL,
            ADD subject VARCHAR(512) NOT NULL,
            ADD body_above_video VARCHAR(4096) NOT NULL,
            ADD body_below_video VARCHAR(4096) NOT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE video_mailings DROP receiver_mail_address, DROP subject, DROP body_above_video, DROP body_below_video');
    }
}
