<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230123152651 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE tus_uploads
            (
                id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)',
                users_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)',
                token CHAR(36) NOT NULL COMMENT '(DC2Type:guid)',
                file_name VARCHAR(256) NOT NULL,
                file_type VARCHAR(32) NOT NULL,
                created_at DATETIME NOT NULL,
                UNIQUE INDEX UNIQ_7B49605D5F37A13B (token),
                INDEX IDX_7B49605D67B3B43D (users_id),
                INDEX created_at_idx (created_at),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB"
        );
        $this->addSql('ALTER TABLE tus_uploads ADD CONSTRAINT FK_7B49605D67B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tus_uploads DROP FOREIGN KEY FK_7B49605D67B3B43D');
        $this->addSql('DROP TABLE tus_uploads');
    }
}
