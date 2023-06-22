<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230622173242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE subscriptions SET membership_plan_name = 'professional' WHERE membership_plan_name = 'pro'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE subscriptions SET membership_plan_name = 'pro' WHERE membership_plan_name = 'professional'");
    }
}
