<?php

namespace App\Tests\UnitTests\VideoBasedMarketing\LingoSync\Infrastructure\Service;


use App\VideoBasedMarketing\LingoSync\Infrastructure\Service\TextToSpeechService;
use PHPUnit\Framework\TestCase;

class TextToSpeechServiceTest
    extends TestCase
{
    private string $webVtt = <<<'EOT'
WEBVTT

1
00:00:00.200 --> 00:00:02.520
Hi, in this video I'll show you how to

2
00:00:02.550 --> 00:00:06.900
make video recordings of yourself and
your screen in very simple steps.
EOT;

    public function testTimestampToMilliseconds(): void
    {
        $this->assertEquals(
            1320,
            TextToSpeechService::timestampToMilliseconds('00:00:01.32')
        );

        $this->assertEquals(
            2500,
            TextToSpeechService::timestampToMilliseconds('00:00:02.50')
        );

        $this->assertEquals(
            2990,
            TextToSpeechService::timestampToMilliseconds('00:00:02.99')
        );

        $this->assertEquals(
            2999,
            TextToSpeechService::timestampToMilliseconds('00:00:02.999')
        );

        $this->assertEquals(
            62999,
            TextToSpeechService::timestampToMilliseconds('00:01:02.999')
        );
    }

    public function testGetWebVttInitialSilenceDuration(): void
    {
        $result = TextToSpeechService::getWebVttInitialSilenceDuration($this->webVtt);

        $this->assertEquals(200, $result);
    }

    public function testGetWebVttDurations(): void
    {
        $result = TextToSpeechService::getWebVttDurations($this->webVtt);

        $this->assertEquals([2320, 4350], $result);
    }

    public function testGetWebVttTexts(): void
    {
        $result = TextToSpeechService::getWebVttTexts($this->webVtt);

        $this->assertEquals(
            [
                "Hi, in this video I'll show you how to",
                'make video recordings of yourself and your screen in very simple steps.'
            ],
            $result
        );
    }
}
