<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221127103847 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE recording_request_responses
            (
                id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)',
                users_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)',
                recording_requests_id CHAR(36) DEFAULT NULL COMMENT '(DC2Type:guid)',
                created_at DATETIME NOT NULL,
                status VARCHAR(255) NOT NULL,
                INDEX IDX_EECF842B67B3B43D (users_id),
                INDEX IDX_EECF842BB797BC9D (recording_requests_id),
                INDEX created_at_idx (created_at),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4 
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ");

        $this->addSql("
            CREATE TABLE recording_requests
            (
                id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)',
                users_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)',
                short_id VARCHAR(12) DEFAULT NULL,
                created_at DATETIME NOT NULL,
                UNIQUE INDEX UNIQ_D8B6801EF8496E51 (short_id),
                INDEX IDX_D8B6801E67B3B43D (users_id),
                INDEX created_at_idx (created_at),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ");

        $this->addSql('ALTER TABLE recording_request_responses ADD CONSTRAINT FK_EECF842B67B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recording_request_responses ADD CONSTRAINT FK_EECF842B6978C560 FOREIGN KEY (recording_requests_id) REFERENCES recording_requests (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recording_requests ADD CONSTRAINT FK_D8B6801E67B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recording_request_responses DROP FOREIGN KEY FK_EECF842B67B3B43D');
        $this->addSql('ALTER TABLE recording_request_responses DROP FOREIGN KEY FK_EECF842BB797BC9D');
        $this->addSql('ALTER TABLE recording_requests DROP FOREIGN KEY FK_D8B6801E67B3B43D');
        $this->addSql('DROP TABLE recording_request_responses');
        $this->addSql('DROP TABLE recording_requests');
    }
}
