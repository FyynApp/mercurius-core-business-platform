<?php

namespace App\Tests\UnitTests\VideoBasedMarketing\LingoSync\Infrastructure\Service;


use App\VideoBasedMarketing\LingoSync\Infrastructure\Service\LingoSyncInfrastructureService;
use PHPUnit\Framework\TestCase;

class LingoSyncInfrastructureServiceTest
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

    public function testCompactizeWebvttOne()
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
And the best thing is, you don't even need e.

4
00:00:09.550 --> 00:00:13.060
g. to install any software or
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
And the best thing is, you don't even need e.g. to install any software or have any technical background.

3
00:00:13.090 --> 00:00:18.220
To make video recordings directly in your browser, you only need a small extension.

4
00:00:18.240 --> 00:00:29.480
In this video, I'm going to show you how to set up this extension so that you can start recording video of yourself right away and easily share it with friends, colleagues and clients.";

        $actualResult = LingoSyncInfrastructureService::compactizeWebVtt($webVtt);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testCleanupPseudoSentencesInWebVttOne(): void
    {
        $originalWebVTT = "WEBVTT

1
00:04:30.160 --> 00:04:37.140
Meiner Meinung nach war dies also höchstwahrscheinlich keine Operation unter falscher Flagge, zumindest nicht in dem Sinne, dass Putin dahinter steckt.

2
00:04:37.160 --> 00:04:40.420
Natürlich könnte es sich um eine andere Gruppe von Russen handeln, z.

3
00:04:40.440 --> 00:04:52.140
B. um russische Nationalisten, die für den Krieg sind, aber ich glaube nicht, dass es sich um eine Operation unter falscher Flagge handelte, bei der der Kreml einen Angriff auf sich selbst inszeniert hat, denn die Merkmale sind einfach nicht vorhanden.

4
00:04:52.160 --> 00:04:53.820
Okay, das war nur ein kurzes Video.";


        $expectedWebVTT = "WEBVTT

1
00:04:30.160 --> 00:04:37.140
Meiner Meinung nach war dies also höchstwahrscheinlich keine Operation unter falscher Flagge, zumindest nicht in dem Sinne, dass Putin dahinter steckt.

2
00:04:37.160 --> 00:04:52.140
Natürlich könnte es sich um eine andere Gruppe von Russen handeln, z.B. um russische Nationalisten, die für den Krieg sind, aber ich glaube nicht, dass es sich um eine Operation unter falscher Flagge handelte, bei der der Kreml einen Angriff auf sich selbst inszeniert hat, denn die Merkmale sind einfach nicht vorhanden.

3
00:04:52.160 --> 00:04:53.820
Okay, das war nur ein kurzes Video.";

        $cleanedUpWebVTT = LingoSyncInfrastructureService::cleanupPseudoSentencesInWebVtt(
            $originalWebVTT,
            ['z.b.', 'u.a.']
        );

        $this->assertSame($expectedWebVTT, $cleanedUpWebVTT);
    }

    public function testCleanupPseudoSentencesInWebVttTwo(): void
    {
        $originalWebVTT = "WEBVTT

1
00:04:30.160 --> 00:04:37.140
Meiner Meinung nach war dies also höchstwahrscheinlich keine Operation unter falscher Flagge, zumindest nicht in dem Sinne, dass Putin dahinter steckt.

2
00:04:40.440 --> 00:04:52.140
Natürlich könnte es sich um eine andere Gruppe von Russen handeln,

3
00:04:52.160 --> 00:04:53.820
wie zum Beispiel um russische Nationalisten, die für den Krieg sind, aber ich glaube nicht, dass es sich um eine Operation unter falscher Flagge handelte, bei der der Kreml einen Angriff auf sich selbst inszeniert hat, denn die Merkmale sind einfach nicht vorhanden.

4
00:04:55.260 --> 00:04:56.920
Okay, das war nur ein kurzes Video.";


        $expectedWebVTT = "WEBVTT

1
00:04:30.160 --> 00:04:37.140
Meiner Meinung nach war dies also höchstwahrscheinlich keine Operation unter falscher Flagge, zumindest nicht in dem Sinne, dass Putin dahinter steckt.

2
00:04:40.440 --> 00:04:52.140
Natürlich könnte es sich um eine andere Gruppe von Russen handeln,

3
00:04:52.160 --> 00:04:53.820
wie zum Beispiel um russische Nationalisten, die für den Krieg sind, aber ich glaube nicht, dass es sich um eine Operation unter falscher Flagge handelte, bei der der Kreml einen Angriff auf sich selbst inszeniert hat, denn die Merkmale sind einfach nicht vorhanden.

4
00:04:55.260 --> 00:04:56.920
Okay, das war nur ein kurzes Video.";

        $cleanedUpWebVTT = LingoSyncInfrastructureService::cleanupPseudoSentencesInWebVtt(
            $originalWebVTT,
            ['z.b.', 'u.a.']
        );

        $this->assertSame($expectedWebVTT, $cleanedUpWebVTT);
    }

    public function testCleanupPseudoSentencesInWebVttThree(): void
    {
        $originalWebVTT = "WEBVTT

1
00:04:30.160 --> 00:04:37.140
Meiner Meinung nach war dies also höchstwahrscheinlich keine Operation unter falscher Flagge, zumindest nicht in dem Sinne, dass Putin dahinter steckt.

2
00:04:37.160 --> 00:04:40.420
Natürlich könnte es sich um eine andere Gruppe von Russen handeln, u.

3
00:04:40.440 --> 00:04:52.140
a. um russische Nationalisten, die für den Krieg sind, aber ich glaube nicht, dass es sich um eine Operation unter falscher Flagge handelte, bei der der Kreml einen Angriff auf sich selbst inszeniert hat, denn die Merkmale sind einfach nicht vorhanden.

4
00:04:52.160 --> 00:04:53.820
Okay, das war nur ein kurzes Video.";


        $expectedWebVTT = "WEBVTT

1
00:04:30.160 --> 00:04:37.140
Meiner Meinung nach war dies also höchstwahrscheinlich keine Operation unter falscher Flagge, zumindest nicht in dem Sinne, dass Putin dahinter steckt.

2
00:04:37.160 --> 00:04:52.140
Natürlich könnte es sich um eine andere Gruppe von Russen handeln, u.a. um russische Nationalisten, die für den Krieg sind, aber ich glaube nicht, dass es sich um eine Operation unter falscher Flagge handelte, bei der der Kreml einen Angriff auf sich selbst inszeniert hat, denn die Merkmale sind einfach nicht vorhanden.

3
00:04:52.160 --> 00:04:53.820
Okay, das war nur ein kurzes Video.";

        $cleanedUpWebVTT = LingoSyncInfrastructureService::cleanupPseudoSentencesInWebVtt(
            $originalWebVTT,
            ['z.b.', 'u.a.']
        );

        $this->assertSame($expectedWebVTT, $cleanedUpWebVTT);
    }

    public function testMapWebVttTimestamps(): void
    {
        $webVTT1 = "WEBVTT

1
00:04:30.160 --> 00:04:37.140
This is the first sentence.

2
00:05:33.260 --> 00:06:37.140
This is the seconds sentence.

3
00:09:33.260 --> 00:11:37.140
This is the third sentence.
";


        $webVTT2 = "WEBVTT

1
00:04:39.160 --> 00:05:09.140
Das ist der erste Satz.

2
00:05:45.260 --> 00:07:37.140
Das ist der zweite Satz.

3
00:08:33.260 --> 00:10:31.140
Das ist der dritte Satz.
";


        $expectedWebVTT = "WEBVTT

1
00:04:30.160 --> 00:04:37.140
Das ist der erste Satz.

2
00:05:33.260 --> 00:06:37.140
Das ist der zweite Satz.

3
00:09:33.260 --> 00:11:37.140
Das ist der dritte Satz.";

        $actualWebVTT = LingoSyncInfrastructureService::mapWebVttTimestamps($webVTT1, $webVTT2);

        $this->assertSame($expectedWebVTT, $actualWebVTT);
    }

    public function testInsertMissingLinebreaksToWebVttOne(): void
    {
        $InvalidWebVtt = "WEBVTT

1
00:01:46.410 --> 00:01:51.340
Zeile eins.

2
00:01:51.370 --> 00:01:53.420
Zeile zwei.

3
00:01:53.450 --> 00:01:54.820
Zeile drei.
4
00:01:54.850 --> 00:02:01.700
Zeile vier.

5
00:02:01.730 --> 00:02:12.660
Zeile fünf.

6
00:02:12.690 --> 00:02:22.500
Zeile sechs.        
";

        $expectedWebVtt = "WEBVTT

1
00:01:46.410 --> 00:01:51.340
Zeile eins.

2
00:01:51.370 --> 00:01:53.420
Zeile zwei.

3
00:01:53.450 --> 00:01:54.820
Zeile drei.

4
00:01:54.850 --> 00:02:01.700
Zeile vier.

5
00:02:01.730 --> 00:02:12.660
Zeile fünf.

6
00:02:12.690 --> 00:02:22.500
Zeile sechs.        
";

        $this->assertSame(
            $expectedWebVtt,
            LingoSyncInfrastructureService::insertMissingLinebreaksToWebVtt($InvalidWebVtt)
        );
    }

    public function testInsertMissingLinebreaksToWebVttTwo(): void
    {
        $InvalidWebVtt = "WEBVTT

1
00:01:46.410 --> 00:01:51.340
Zeile eins.

2
00:01:51.370 --> 00:01:53.420
Zeile zwei.

3
00:01:53.450 --> 00:01:54.820
Zeile drei.

4
00:01:54.850 --> 00:02:01.700
Zeile vier.

5
00:02:01.730 --> 00:02:12.660
Zeile fünf.

6
00:02:12.690 --> 00:02:22.500
Zeile sechs.        
";

        $expectedWebVtt = "WEBVTT

1
00:01:46.410 --> 00:01:51.340
Zeile eins.

2
00:01:51.370 --> 00:01:53.420
Zeile zwei.

3
00:01:53.450 --> 00:01:54.820
Zeile drei.

4
00:01:54.850 --> 00:02:01.700
Zeile vier.

5
00:02:01.730 --> 00:02:12.660
Zeile fünf.

6
00:02:12.690 --> 00:02:22.500
Zeile sechs.        
";

        $this->assertSame(
            $expectedWebVtt,
            LingoSyncInfrastructureService::insertMissingLinebreaksToWebVtt($InvalidWebVtt)
        );
    }

    public function testWebVttIsValid(): void
    {
        $validWebVtt = "WEBVTT
        
1
00:00:00.000 --> 00:00:01.440
Hello, World.
";

        $this->assertTrue(LingoSyncInfrastructureService::webVttIsValid($validWebVtt));


        $invalidWebVtt = "WEBVTT

";

        $this->assertFalse(LingoSyncInfrastructureService::webVttIsValid($invalidWebVtt));
    }
}
