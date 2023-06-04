<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\Service;

use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionWebVtt;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Utility\WebVttParser;


readonly class WebVttParserService
{
    public function getText(
        AudioTranscriptionWebVtt $webVtt
    ): string
    {
        $parser = new WebVttParser();
        $cues = $parser->parse($webVtt->getVttContent())['cues'];

        $text = '';

        foreach ($cues as $cue) {
            if (array_key_exists('text', $cue)) {
                $text .= $cue['text'] . ' ';
            }
        }

        return $text;
    }
}
