<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\SymfonyMessage;

use App\Shared\Infrastructure\SymfonyMessage\AsyncSymfonyMessageInterface;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeExport;


readonly class CheckHappyScribeExportCommandSymfonyMessage
    implements AsyncSymfonyMessageInterface
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
