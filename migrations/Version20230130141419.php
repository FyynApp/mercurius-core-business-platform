<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230130141419 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE custom_domain_settings 
            (
                id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                domain_name VARCHAR(256) DEFAULT NULL,
                check_status SMALLINT NOT NULL,
                created_at DATETIME NOT NULL,
                UNIQUE INDEX UNIQ_90E7317567B3B43D (users_id),
                INDEX created_at_idx (created_at),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ');

        $this->addSql('ALTER TABLE custom_domain_settings ADD CONSTRAINT FK_90E7317567B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE custom_logo_settings DROP INDEX IDX_3F6AB5E867B3B43D, ADD UNIQUE INDEX UNIQ_3F6AB5E867B3B43D (users_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE custom_domain_settings DROP FOREIGN KEY FK_90E7317567B3B43D');
        $this->addSql('DROP TABLE custom_domain_settings');
        $this->addSql('ALTER TABLE custom_logo_settings DROP INDEX UNIQ_3F6AB5E867B3B43D, ADD INDEX IDX_3F6AB5E867B3B43D (users_id)');
    }
}
