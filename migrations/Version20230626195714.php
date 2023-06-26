<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230626195714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE lingosync_credit_positions
            (
                id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                causing_subscriptions_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\',
                causing_purchases_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\',
                causing_lingosync_processes_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\',
                causing_users_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\',
                owning_users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                amount INT NOT NULL,
                INDEX IDX_AD500A286AF9803B (causing_subscriptions_id),
                INDEX IDX_AD500A288B7BBAD8 (causing_purchases_id),
                INDEX IDX_AD500A28666C4D38 (causing_lingosync_processes_id),
                INDEX IDX_AD500A28451D3014 (causing_users_id),
                INDEX IDX_AD500A2891CA5BAF (owning_users_id),
                INDEX created_at_idx (created_at),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ');

        $this->addSql('CREATE TABLE purchases (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', package_name VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_AA6431FE67B3B43D (users_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE lingosync_credit_positions ADD CONSTRAINT FK_AD500A286AF9803B FOREIGN KEY (causing_subscriptions_id) REFERENCES subscriptions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lingosync_credit_positions ADD CONSTRAINT FK_AD500A288B7BBAD8 FOREIGN KEY (causing_purchases_id) REFERENCES purchases (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lingosync_credit_positions ADD CONSTRAINT FK_AD500A28666C4D38 FOREIGN KEY (causing_lingosync_processes_id) REFERENCES lingosync_processes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lingosync_credit_positions ADD CONSTRAINT FK_AD500A28451D3014 FOREIGN KEY (causing_users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lingosync_credit_positions ADD CONSTRAINT FK_AD500A2891CA5BAF FOREIGN KEY (owning_users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE purchases ADD CONSTRAINT FK_AA6431FE67B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lingosync_credit_positions DROP FOREIGN KEY FK_AD500A286AF9803B');
        $this->addSql('ALTER TABLE lingosync_credit_positions DROP FOREIGN KEY FK_AD500A288B7BBAD8');
        $this->addSql('ALTER TABLE lingosync_credit_positions DROP FOREIGN KEY FK_AD500A28666C4D38');
        $this->addSql('ALTER TABLE lingosync_credit_positions DROP FOREIGN KEY FK_AD500A28451D3014');
        $this->addSql('ALTER TABLE lingosync_credit_positions DROP FOREIGN KEY FK_AD500A2891CA5BAF');
        $this->addSql('ALTER TABLE purchases DROP FOREIGN KEY FK_AA6431FE67B3B43D');
        $this->addSql('DROP TABLE lingosync_credit_positions');
        $this->addSql('DROP TABLE purchases');
    }
}
