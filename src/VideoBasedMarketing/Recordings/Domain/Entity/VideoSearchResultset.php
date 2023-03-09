<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Entity;

use ValueError;


readonly class VideoSearchResultset
{
    private array $videoSearchResults;

    public function __construct(
        array $videoSearchResults
    )
    {
        foreach ($videoSearchResults as $videoSearchResult) {
            if (!($videoSearchResult instanceof VideoSearchResult)) {
                throw new ValueError();
            }
        }
        $this->videoSearchResults = $videoSearchResults;
    }

    /** @return VideoSearchResult[] */
    public function getResults(): array
    {
        return $this->videoSearchResults;
    }
}
