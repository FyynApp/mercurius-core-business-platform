<?php

namespace App\Tests\UnitTests\VideoBasedMarketing\AudioTranscription\Infrastructure\Utility;

use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Utility\WebVttParser;
use PHPUnit\Framework\TestCase;

class WebVttParserTest
    extends TestCase
{
    public function test(): void
    {
        $webVttContent = <<<EOT
WEBVTT

1
00:00:00.240 --> 00:00:04.420
Hi, in this video I will show you how to
easily create a video recording of

2
00:00:04.450 --> 00:00:07.690
yourself and your screen
using just your browser.

3
00:00:07.720 --> 00:00:08.420
And the best part.

4
00:00:08.450 --> 00:00:11.220
Is, you don't even need
to install any software.

5
00:00:11.240 --> 00:00:12.260
Or opposes.

6
00:00:12.290 --> 00:00:13.540
Technical skills.

7
00:00:13.570 --> 00:00:17.660
To record a video directly in your
browser, all you need is a small.

8
00:00:17.690 --> 00:00:20.260
Extension that enables this feature.

9
00:00:20.290 --> 00:00:22.700
I will show you how to set it up.

10
00:00:22.730 --> 00:00:25.180
In just a few steps so you can start

11
00:00:25.200 --> 00:00:28.120
easily share them with your
friends, colleagues, and customers.

EOT;

        $parser = new WebVttParser();
        $cues = $parser->parse($webVttContent)['cues'];

        $text = '';
        foreach ($cues as $cue) {
            $text .= $cue['text'] . ' ';
        }

        $this->assertSame(
            "Hi, in this video I will show you how toeasily create a video recording of yourself and your screenusing just your browser. And the best part. Is, you don't even needto install any software. Or opposes. Technical skills. To record a video directly in yourbrowser, all you need is a small. Extension that enables this feature. I will show you how to set it up. In just a few steps so you can start easily share them with yourfriends, colleagues, and customers.",
            trim($text)
        );
    }
}
