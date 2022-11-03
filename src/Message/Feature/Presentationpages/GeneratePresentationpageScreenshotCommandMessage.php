<?php

namespace App\Message\Feature\Presentationpages;

use App\Entity\Feature\Presentationpages\Presentationpage;
use App\Message\AsyncMessageInterface;
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
