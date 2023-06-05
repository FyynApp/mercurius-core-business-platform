<?php

namespace App\VideoBasedMarketing\LingoSync\Infrastructure\Service;


use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\Shared\Domain\Enum\Gender;
use App\VideoBasedMarketing\LingoSync\Infrastructure\ApiClient\GoogleCloudTextToSpeechApiClient;
use Symfony\Component\Process\Process;

readonly class TextToSpeechService
{
    public function __construct(
        private GoogleCloudTextToSpeechApiClient $googleCloudTextToSpeechApiClient
    )
    {
    }

    public function createAudioFileFromText(
        string            $text,
        Bcp47LanguageCode $languageCode,
        Gender            $gender,
        float             $speakingRate,
        string            $audioFilePath
    ): void
    {
        $this->googleCloudTextToSpeechApiClient->createAudioFileFromText(
            $text,
            $languageCode,
            $gender,
            $speakingRate,
            $audioFilePath
        );
    }

    public function getAudioFileLength(
        string $audioFilePath
    ): ?float
    {
        $output = shell_exec(
            'ffmpeg -i ' . $audioFilePath . ' 2>&1 | grep Duration | cut -d " " -f 4 | sed s/,//'
        );

        return self::timestampToMilliseconds($output);
    }

    public function speedupAudioFile(
        string $sourceAudioFilePath,
        string $targetAudioFilePath,
        float  $speakingRate
    ): void
    {
        // ffmpeg -i test.mp3 -filter:a "atempo=2.0" test.fast.mp3

        $process = new Process(
            [
                'ffmpeg',

                '-i',
                $sourceAudioFilePath,

                '-filter:a',
                'atempo=' . $speakingRate,

                '-y',
                $targetAudioFilePath
            ]
        );
        $process->setTimeout(60 * 2);
        $process->run();
    }

    public static function timestampToMilliseconds(string $timestamp): int
    {
        list($h, $m, $s) = explode(":", $timestamp);

        return (int)(((int)$h * 3600 + (int)$m * 60 + (float)$s) * 1000);
    }

    public static function getWebVttInitialSilenceDuration(string $webVtt): int
    {
        // This regular expression matches a WebVTT timestamp
        preg_match("/\d{2}:\d{2}:\d{2}\.\d{3}/", $webVtt, $matches);

        // The first match is the first timestamp
        $firstTimestamp = $matches[0];

        // Convert the first timestamp into milliseconds
        return self::timestampToMilliseconds($firstTimestamp);
    }

    public static function getWebVttDurations(string $webVtt): array
    {
        // Split the string into cues
        $cues = preg_split('/\s*\n\s*\n\s*/', trim($webVtt));

        // Remove WEBVTT header
        if (strtoupper(substr($cues[0], 0, 6)) === 'WEBVTT') {
            array_shift($cues);
        }

        $durations = [];
        foreach ($cues as $cue) {
            // Extract the timestamp line
            if (preg_match('/(\d{2}:\d{2}:\d{2}\.\d{3}) --> (\d{2}:\d{2}:\d{2}\.\d{3})/', $cue, $matches)) {
                // Calculate and store the duration
                $start = self::timestampToMilliseconds($matches[1]);
                $end = self::timestampToMilliseconds($matches[2]);
                $durations[] = $end - $start;
            }
        }

        return $durations;
    }

    public static function getWebVttTexts(string $webVtt): array
    {
        // Split by two or more newline characters to split up the sections
        $webVttSections = preg_split("/\n{2,}/", $webVtt);
        $texts = [];

        foreach ($webVttSections as $section) {
            // If the section doesn't contain '-->', it's not a caption
            if (mb_strpos($section, '-->') === false) {
                continue;
            }

            // Split by newline characters, and ignore the first two lines (index and timestamp)
            $lines = preg_split("/\n/", $section);
            $lines = array_slice($lines, 2);

            // Join the lines together with a space, and add them to the texts
            $texts[] = implode(' ', $lines);
        }

        return $texts;
    }
}
