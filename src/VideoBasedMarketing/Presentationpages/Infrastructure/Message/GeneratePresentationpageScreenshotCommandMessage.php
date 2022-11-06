<?php

namespace App\VideoBasedMarketing\Presentationpages\Infrastructure\Message;

use App\Shared\Infrastructure\Message\AsyncMessageInterface;
use App\VideoBasedMarketing\Presentationpages\Domain\Entity\Presentationpage;
use InvalidArgumentException;


class GeneratePresentationpageScreenshotCommandMessage
    implements AsyncMessageInterface
{
    private string $presentationpageId;

    public function __construct(Presentationpage $presentationpage)
    {
        if (is_null($presentationpage->getId())) {
            throw new InvalidArgumentException('presentationpage needs an id.');
        }
        $this->presentationpageId = $presentationpage->getId();
    }

    public function getPresentationpageId(): string
    {
        return $this->presentationpageId;
    }
}
