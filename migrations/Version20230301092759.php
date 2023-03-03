<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230301092759 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE presentationpages DROP FOREIGN KEY FK_7BF6352167B3B43D');
        $this->addSql('ALTER TABLE presentationpages ADD organizations_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', CHANGE users_id users_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE presentationpages ADD CONSTRAINT FK_7BF6352186288A55 FOREIGN KEY (organizations_id) REFERENCES organizations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE presentationpages ADD CONSTRAINT FK_7BF6352167B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_7BF6352186288A55 ON presentationpages (organizations_id)');

        $this->addSql('ALTER TABLE recording_request_responses DROP FOREIGN KEY FK_EECF842B67B3B43D');
        $this->addSql('ALTER TABLE recording_request_responses ADD organizations_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', CHANGE users_id users_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE recording_request_responses ADD CONSTRAINT FK_EECF842B86288A55 FOREIGN KEY (organizations_id) REFERENCES organizations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recording_request_responses ADD CONSTRAINT FK_EECF842B67B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_EECF842B86288A55 ON recording_request_responses (organizations_id)');

        $this->addSql('ALTER TABLE recording_requests DROP FOREIGN KEY FK_D8B6801E67B3B43D');
        $this->addSql('ALTER TABLE recording_requests ADD organizations_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', CHANGE users_id users_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE recording_requests ADD CONSTRAINT FK_D8B6801E86288A55 FOREIGN KEY (organizations_id) REFERENCES organizations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recording_requests ADD CONSTRAINT FK_D8B6801E67B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_D8B6801E86288A55 ON recording_requests (organizations_id)');

        $this->addSql('ALTER TABLE video_mailings DROP FOREIGN KEY FK_3F61587567B3B43D');
        $this->addSql('ALTER TABLE video_mailings ADD organizations_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', CHANGE users_id users_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE video_mailings ADD CONSTRAINT FK_3F61587586288A55 FOREIGN KEY (organizations_id) REFERENCES organizations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE video_mailings ADD CONSTRAINT FK_3F61587567B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_3F61587586288A55 ON video_mailings (organizations_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE video_mailings DROP FOREIGN KEY FK_3F61587586288A55');
        $this->addSql('ALTER TABLE video_mailings DROP FOREIGN KEY FK_3F61587567B3B43D');
        $this->addSql('DROP INDEX IDX_3F61587586288A55 ON video_mailings');
        $this->addSql('ALTER TABLE video_mailings DROP organizations_id, CHANGE users_id users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE video_mailings ADD CONSTRAINT FK_3F61587567B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recording_request_responses DROP FOREIGN KEY FK_EECF842B86288A55');
        $this->addSql('ALTER TABLE recording_request_responses DROP FOREIGN KEY FK_EECF842B67B3B43D');
        $this->addSql('DROP INDEX IDX_EECF842B86288A55 ON recording_request_responses');
        $this->addSql('ALTER TABLE recording_request_responses DROP organizations_id, CHANGE users_id users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE recording_request_responses ADD CONSTRAINT FK_EECF842B67B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE presentationpages DROP FOREIGN KEY FK_7BF6352186288A55');
        $this->addSql('ALTER TABLE presentationpages DROP FOREIGN KEY FK_7BF6352167B3B43D');
        $this->addSql('DROP INDEX IDX_7BF6352186288A55 ON presentationpages');
        $this->addSql('ALTER TABLE presentationpages DROP organizations_id, CHANGE users_id users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE presentationpages ADD CONSTRAINT FK_7BF6352167B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recording_requests DROP FOREIGN KEY FK_D8B6801E86288A55');
        $this->addSql('ALTER TABLE recording_requests DROP FOREIGN KEY FK_D8B6801E67B3B43D');
        $this->addSql('DROP INDEX IDX_D8B6801E86288A55 ON recording_requests');
        $this->addSql('ALTER TABLE recording_requests DROP organizations_id, CHANGE users_id users_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE recording_requests ADD CONSTRAINT FK_D8B6801E67B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
    }
}
