<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Entity;

use ValueError;


readonly class VideoFinderResultset
{
    private array $videoFinderResults;

    public function __construct(
        array $videoFinderResults
    )
    {
        foreach ($videoFinderResults as $videoFinderResult) {
            if (!($videoFinderResult instanceof VideoFinderResult)) {
                throw new ValueError();
            }
        }
        $this->videoFinderResults = $videoFinderResults;
    }

    /** @return VideoFinderResult[] */
    public function getResults(): array
    {
        return $this->videoFinderResults;
    }
}
