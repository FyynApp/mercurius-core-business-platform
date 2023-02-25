<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230225110227 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organization_invitations DROP FOREIGN KEY FK_137BB4D571530DFE');
        $this->addSql('DROP INDEX IDX_137BB4D571530DFE ON organization_invitations');
        $this->addSql('ALTER TABLE organization_invitations CHANGE owner_users_id organizations_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE organization_invitations ADD CONSTRAINT FK_137BB4D586288A55 FOREIGN KEY (organizations_id) REFERENCES organizations (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_137BB4D586288A55 ON organization_invitations (organizations_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organization_invitations DROP FOREIGN KEY FK_137BB4D586288A55');
        $this->addSql('DROP INDEX IDX_137BB4D586288A55 ON organization_invitations');
        $this->addSql('ALTER TABLE organization_invitations CHANGE organizations_id owner_users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE organization_invitations ADD CONSTRAINT FK_137BB4D571530DFE FOREIGN KEY (owner_users_id) REFERENCES organizations (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_137BB4D571530DFE ON organization_invitations (owner_users_id)');
    }
}
