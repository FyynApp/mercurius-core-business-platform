<?php

namespace App\Tests\UnitTests\VideoBasedMarketing\LingoSync\Infrastructure\Service;


use App\VideoBasedMarketing\LingoSync\Infrastructure\Service\TextToSpeechService;
use PHPUnit\Framework\TestCase;

class TextToSpeechServiceTest
    extends TestCase
{
    private string $webVttNormal = <<<'EOT'
WEBVTT

1
00:00:00.200 --> 00:00:02.520
Hi, in this video I'll show you how to

2
00:00:02.550 --> 00:00:06.900
make video recordings of yourself and
your screen in very simple steps.

3
00:01:10.440 --> 00:01:10.995
Hello, World.
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

    public function testGetWebVttStarts(): void
    {
        $result = TextToSpeechService::getWebVttStarts($this->webVttNormal);

        $this->assertEquals([200, 2550, 70440], $result);
    }

    public function testGetWebVttDurations(): void
    {
        $result = TextToSpeechService::getWebVttDurationsInMilliseconds($this->webVttNormal);

        $this->assertEquals([2320, 4350, 555], $result);
    }

    public function testGetWebVttTexts(): void
    {
        $result = TextToSpeechService::getWebVttTexts($this->webVttNormal);

        $this->assertEquals(
            [
                "Hi, in this video I'll show you how to",
                'make video recordings of yourself and your screen in very simple steps.',
                'Hello, World.'
            ],
            $result
        );
    }

    public function testCompactizeWebvtt()
    {
        $webVtt = "WEBVTT

1
00:00:00.200 --> 00:00:02.520
Hi, in this video I'll show you how to

2
00:00:02.550 --> 00:00:06.900
make video recordings of yourself and
your screen in very simple steps

3
00:00:06.930 --> 00:00:09.520
And the best thing is, you don't even need

4
00:00:09.550 --> 00:00:13.060
to install any software or
have any technical background.";

        $expectedResult = "WEBVTT

1
00:00:00.200 --> 00:00:06.900
Hi, in this video I'll show you how to make video recordings of yourself and your screen in very simple steps.

2
00:00:06.930 --> 00:00:13.060
And the best thing is, you don't even need to install any software or have any technical background.";

        $actualResult = TextToSpeechService::compactizeWebvtt($webVtt);

        $this->assertEquals($expectedResult, $actualResult);
    }
}
