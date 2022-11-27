<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221127105542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recording_request_responses DROP FOREIGN KEY FK_EECF842B6978C560');
        $this->addSql('ALTER TABLE recording_request_responses CHANGE recording_requests_id recording_requests_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('DROP INDEX idx_eecf842bb797bc9d ON recording_request_responses');
        $this->addSql('CREATE INDEX IDX_EECF842B6978C560 ON recording_request_responses (recording_requests_id)');
        $this->addSql('ALTER TABLE recording_request_responses ADD CONSTRAINT FK_EECF842B6978C560 FOREIGN KEY (recording_requests_id) REFERENCES recording_requests (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE videos ADD recording_request_responses_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE videos ADD CONSTRAINT FK_29AA643266DEEB1 FOREIGN KEY (recording_request_responses_id) REFERENCES recording_request_responses (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_29AA643266DEEB1 ON videos (recording_request_responses_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE videos DROP FOREIGN KEY FK_29AA643266DEEB1');
        $this->addSql('DROP INDEX IDX_29AA643266DEEB1 ON videos');
        $this->addSql('ALTER TABLE videos DROP recording_request_responses_id');
        $this->addSql('ALTER TABLE recording_request_responses DROP FOREIGN KEY FK_EECF842B6978C560');
        $this->addSql('ALTER TABLE recording_request_responses CHANGE recording_requests_id recording_requests_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('DROP INDEX idx_eecf842b6978c560 ON recording_request_responses');
        $this->addSql('CREATE INDEX IDX_EECF842BB797BC9D ON recording_request_responses (recording_requests_id)');
        $this->addSql('ALTER TABLE recording_request_responses ADD CONSTRAINT FK_EECF842B6978C560 FOREIGN KEY (recording_requests_id) REFERENCES recording_requests (id) ON DELETE CASCADE');
    }
}
