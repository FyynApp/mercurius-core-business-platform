<?php

namespace App\Tests\UnitTests\VideoBasedMarketing\LingoSync\Infrastructure\Service;


use App\VideoBasedMarketing\LingoSync\Infrastructure\Service\LingoSyncInfrastructureService;
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
            LingoSyncInfrastructureService::timestampToMilliseconds('00:00:01.32')
        );

        $this->assertEquals(
            2500,
            LingoSyncInfrastructureService::timestampToMilliseconds('00:00:02.50')
        );

        $this->assertEquals(
            2990,
            LingoSyncInfrastructureService::timestampToMilliseconds('00:00:02.99')
        );

        $this->assertEquals(
            2999,
            LingoSyncInfrastructureService::timestampToMilliseconds('00:00:02.999')
        );

        $this->assertEquals(
            62999,
            LingoSyncInfrastructureService::timestampToMilliseconds('00:01:02.999')
        );
    }

    public function testMillisecondsToTimestamp(): void
    {
        $this->assertEquals(
            '00:00:01.320',
            LingoSyncInfrastructureService::millisecondsToTimestamp(1320)
        );

        $this->assertEquals(
            '00:00:02.500',
            LingoSyncInfrastructureService::millisecondsToTimestamp(2500)
        );

        $this->assertEquals(
            '00:00:02.990',
            LingoSyncInfrastructureService::millisecondsToTimestamp(2990)
        );

        $this->assertEquals(
            '00:01:02.999',
            LingoSyncInfrastructureService::millisecondsToTimestamp(62999)
        );
    }

    public function testGetWebVttStarts(): void
    {
        $result = LingoSyncInfrastructureService::getWebVttStartsAsMilliseconds($this->webVttNormal);

        $this->assertEquals([200, 2550, 70440], $result);
    }

    public function testGetWebVttDurations(): void
    {
        $result = LingoSyncInfrastructureService::getWebVttDurationsAsMilliseconds($this->webVttNormal);

        $this->assertEquals([2320, 4350, 555], $result);
    }

    public function testGetWebVttTexts(): void
    {
        $result = LingoSyncInfrastructureService::getWebVttTexts($this->webVttNormal);

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
your screen in very simple steps!

3
00:00:06.930 --> 00:00:09.520
And the best thing is, you don't even need

4
00:00:09.550 --> 00:00:13.060
to install any software or
have any technical background.

5
00:00:13.090 --> 00:00:18.220
To make video recordings directly in your
browser, you only need a small extension.

6
00:00:18.240 --> 00:00:20.760
In this video, I'm going to show you how

7
00:00:20.790 --> 00:00:25.720
to set up this extension so that you can
start recording video of yourself right

8
00:00:25.740 --> 00:00:29.480
away and easily share it with
friends, colleagues and clients.
";

        $expectedResult = "WEBVTT

1
00:00:00.200 --> 00:00:06.900
Hi, in this video I'll show you how to make video recordings of yourself and your screen in very simple steps!

2
00:00:06.930 --> 00:00:13.060
And the best thing is, you don't even need to install any software or have any technical background.

3
00:00:13.090 --> 00:00:18.220
To make video recordings directly in your browser, you only need a small extension.

4
00:00:18.240 --> 00:00:29.480
In this video, I'm going to show you how to set up this extension so that you can start recording video of yourself right away and easily share it with friends, colleagues and clients.";

        $actualResult = LingoSyncInfrastructureService::compactizeWebVtt($webVtt);

        $this->assertEquals($expectedResult, $actualResult);
    }
}
