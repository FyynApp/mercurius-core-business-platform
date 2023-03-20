<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\Message;

use App\Shared\Infrastructure\Message\AsyncMessageInterface;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeExport;


readonly class CheckHappyScribeExportCommandMessage
    implements AsyncMessageInterface
{
    private string $happyScribeExportId;

    public function __construct(
        HappyScribeExport $happyScribeExport
    )
    {
        $this->happyScribeExportId = $happyScribeExport->getId();
    }

    public function getHappyScribeExportId(): string
    {
        return $this->happyScribeExportId;
    }
}
