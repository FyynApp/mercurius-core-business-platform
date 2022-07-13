<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220713093337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE thirdpartyauth_linkedin_resourceowners
            (
                id VARCHAR(255) NOT NULL,
                email VARCHAR(180) NOT NULL,
                first_name VARCHAR(256) DEFAULT NULL,
                last_name VARCHAR(256) DEFAULT NULL,
                sorted_profile_picture_800_url VARCHAR(2048) DEFAULT NULL,
                sorted_profile_picture_800_content_type VARCHAR(100) DEFAULT NULL,
                UNIQUE INDEX UNIQ_2F1E99A9E7927C74 (email),
                UNIQUE INDEX UNIQ_2F1E99A97A9B7C2E (sorted_profile_picture_800_url),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
            ;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE thirdpartyauth_linkedin_resourceowners;');
    }
}
