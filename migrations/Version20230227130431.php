<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230227130431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE users_organizations 
            (
                users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                organizations_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                INDEX IDX_4B99147267B3B43D (users_id),
                INDEX IDX_4B99147286288A55 (organizations_id),
                PRIMARY KEY(users_id, organizations_id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ');

        $this->addSql('ALTER TABLE users_organizations ADD CONSTRAINT FK_4B99147267B3B43D FOREIGN KEY (users_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE users_organizations ADD CONSTRAINT FK_4B99147286288A55 FOREIGN KEY (organizations_id) REFERENCES organizations (id)');
        $this->addSql('ALTER TABLE organizations DROP INDEX UNIQ_427C1C7F91CA5BAF, ADD INDEX IDX_427C1C7F91CA5BAF (owning_users_id)');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E986288A55');
        $this->addSql('DROP INDEX IDX_1483A5E986288A55 ON users');
        $this->addSql('ALTER TABLE users ADD currently_active_organizations_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', DROP organizations_id');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9F8C1AB04 FOREIGN KEY (currently_active_organizations_id) REFERENCES organizations (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_1483A5E9F8C1AB04 ON users (currently_active_organizations_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users_organizations DROP FOREIGN KEY FK_4B99147267B3B43D');
        $this->addSql('ALTER TABLE users_organizations DROP FOREIGN KEY FK_4B99147286288A55');
        $this->addSql('DROP TABLE users_organizations');
        $this->addSql('ALTER TABLE organizations DROP INDEX IDX_427C1C7F91CA5BAF, ADD UNIQUE INDEX UNIQ_427C1C7F91CA5BAF (owning_users_id)');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9F8C1AB04');
        $this->addSql('DROP INDEX IDX_1483A5E9F8C1AB04 ON users');
        $this->addSql('ALTER TABLE users ADD organizations_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', DROP currently_active_organizations_id');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E986288A55 FOREIGN KEY (organizations_id) REFERENCES organizations (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_1483A5E986288A55 ON users (organizations_id)');
    }
}
