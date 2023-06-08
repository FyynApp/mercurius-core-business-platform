<?php

namespace App\VideoBasedMarketing\Presentationpages\Infrastructure\SymfonyMessage;

use App\Shared\Infrastructure\SymfonyMessage\AsyncSymfonyMessageInterface;
use App\VideoBasedMarketing\Presentationpages\Domain\Entity\Presentationpage;
use InvalidArgumentException;


class GeneratePresentationpageScreenshotCommandSymfonyMessage
    implements AsyncSymfonyMessageInterface
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
