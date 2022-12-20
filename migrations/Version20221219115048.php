<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221219115048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE activecampaign_contacts
            (
                id VARCHAR(255) NOT NULL,
                users_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)',
                UNIQUE INDEX UNIQ_562595C867B3B43D (users_id),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ");

        $this->addSql('
            ALTER TABLE activecampaign_contacts
            ADD CONSTRAINT FK_562595C867B3B43D
            FOREIGN KEY (users_id)
            REFERENCES users (id)
            ON DELETE CASCADE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activecampaign_contacts DROP FOREIGN KEY FK_562595C867B3B43D');
        $this->addSql('DROP TABLE activecampaign_contacts');
    }
}
