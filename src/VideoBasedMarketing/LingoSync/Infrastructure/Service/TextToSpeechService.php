<?php

namespace App\VideoBasedMarketing\LingoSync\Infrastructure\Service;


use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\Shared\Domain\Enum\Gender;
use App\VideoBasedMarketing\LingoSync\Infrastructure\ApiClient\GoogleCloudTextToSpeechApiClient;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

readonly class TextToSpeechService
{
    public function __construct(
        private GoogleCloudTextToSpeechApiClient $googleCloudTextToSpeechApiClient
    )
    {
    }

    public static function compactizeWebvtt(string $webvtt): string {
        // Split the input into cues
        $cues = explode("\n\n", trim($webvtt));

        // Initialize variables
        $transformedCues = [];
        $startTimestampForUpcomingTransformedCue = '';
        $textForUpcomingTransformedCue = '';

        // Iterate over the cues
        foreach ($cues as $cue) {
            // Split the cue into lines
            $lines = explode("\n", $cue);

            if ($lines[0] === 'WEBVTT') {
                continue;
            }

            // Extract the timestampLine and text
            $timestampLine = $lines[1];
            $text = implode(' ', array_slice($lines, 2));

            // Update the start timestampLine if necessary
            if ($startTimestampForUpcomingTransformedCue === '') {
                $startTimestampForUpcomingTransformedCue = explode(' --> ', $timestampLine)[0];
            }

            // Update the end timestampLine and text
            $endTimestampForUpcomingTransformedCue = explode(' --> ', $timestampLine)[1];
            $textForUpcomingTransformedCue .= $text . ' ';

            // If the text ends with a sentence terminator, add a transformed cue
            if (preg_match('/[.!?]$/', $text)) {
                $transformedCues[] = [
                    'startTimestamp' => $startTimestampForUpcomingTransformedCue,
                    'endTimestamp' => $endTimestampForUpcomingTransformedCue,
                    'text' => trim($textForUpcomingTransformedCue)
                ];

                // Reset the variables
                $startTimestampForUpcomingTransformedCue = '';
                $textForUpcomingTransformedCue = '';
            }
        }

        // Build the output
        $output = "WEBVTT\n\n";
        foreach ($transformedCues as $index => $cue) {
            $output .= ($index + 1) . "\n" . $cue['startTimestamp'] . ' --> ' . $cue['endTimestamp'] . "\n" . $cue['text'] . "\n\n";
        }

        return trim($output);
    }

    /**
     * @throws ApiException
     * @throws ValidationException
     */
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

    public static function getAudioFileDurationInMilliseconds(
        string $audioFilePath
    ): ?int
    {
        $output = shell_exec(
            'ffmpeg -i ' . $audioFilePath . ' 2>&1 | grep Duration | cut -d " " -f 4 | sed s/,//'
        );

        return self::timestampToMilliseconds($output);
    }

    public static function trimAudioFile(
        string $sourceAudioFilePath,
        string $targetAudioFilePath
    ): void
    {
        $process = new Process(
            [
                'ffmpeg',

                '-i',
                $sourceAudioFilePath,

                '-af',
                'silenceremove=start_periods=1:start_duration=1:start_threshold=0,areverse,silenceremove=start_periods=1:start_duration=1:start_threshold=0,areverse',

                '-y',
                $targetAudioFilePath
            ]
        );
        $process->setTimeout(60 * 2);
        $process->run();
    }

    public static function speedupAudioFile(
        string $sourceAudioFilePath,
        string $targetAudioFilePath,
        float  $speakingRate
    ): void
    {
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

    public static function getWebVttStartsAsMilliseconds(string $webVtt): array
    {
        // Split the text into separate cues
        $cues = explode("\n\n", $webVtt);
        $starts = [];

        // Discard the "WEBVTT" header
        array_shift($cues);

        foreach ($cues as $cue) {
            // Split each cue into separate lines
            $lines = explode("\n", $cue);

            // Extract the time range line
            $timeRange = $lines[1];

            // Extract the start time
            $start = explode(" --> ", $timeRange)[0];

            // Convert the start time to milliseconds and add to the array
            $starts[] = self::timestampToMilliseconds($start);
        }

        return $starts;
    }

    public static function getWebVttDurationsInMilliseconds(string $webVtt): array
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

                if (is_null($matches[1]) || is_null($matches[2])) {
                    continue;
                }

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

    public static function concatenateAudioFiles(string $webVtt, string $sourceFilesFolderPath, string $targetFilePath): void
    {
        $starts = self::getWebVttStartsAsMilliseconds($webVtt);
        $durations = self::getWebVttDurationsInMilliseconds($webVtt);

        $files = [];
        $filter = '';
        $previousEnd = 0;
        $audioIndex = 0;

        foreach ($starts as $index => $start) {
            $silenceDuration = max(0, $start - $previousEnd) / 1000; // Duration in seconds

            if ($silenceDuration > 0) {
                // Generate a silence audio file of the needed duration
                $silenceFilePath = sys_get_temp_dir() . '/' . uniqid('silence_', true) . '.mp3';
                exec("ffmpeg -y -f lavfi -i anullsrc=channel_layout=stereo:sample_rate=44100 -t {$silenceDuration} {$silenceFilePath}");

                $files[] = $silenceFilePath;
                $filter .= "[{$audioIndex}:a]";
                $audioIndex++;
            }

            $files[] = "{$sourceFilesFolderPath}/{$index}.mp3";
            $filter .= "[{$audioIndex}:a]";
            $audioIndex++;

            $previousEnd = $start + self::getAudioFileDurationInMilliseconds("{$sourceFilesFolderPath}/{$index}.mp3");
        }

        $cmd = "ffmpeg -y -i " . implode(' -i ', $files) . " -filter_complex '{$filter}concat=n={$audioIndex}:v=0:a=1[out]' -map '[out]' {$targetFilePath}";

        // Execute the command
        exec($cmd);

        // Delete the temporary silence audio files
        foreach ($files as $file) {
            if (str_contains($file, 'silence_')) {
                unlink($file);
            }
        }
    }


    /**
     * @throws ValidationException
     * @throws ApiException
     */
    public function createAudioFilesForWebVttCues(
        string            $webVtt,
        Bcp47LanguageCode $languageCode,
        Gender            $gender
    ): string
    {
        $texts = self::getWebVttTexts($webVtt);
        $durations = self::getWebVttDurationsInMilliseconds($webVtt);

        $finalAudioFilesFolderPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid(md5($webVtt), true);
        $fs = new Filesystem();
        $fs->mkdir($finalAudioFilesFolderPath);

        foreach ($texts as $index => $text) {
            $originalAudioFilePath = self::generateTemporaryAudioFilePath($text);
            $this->createAudioFileFromText(
                $text,
                $languageCode,
                $gender,
                1.0,
                $originalAudioFilePath
            );

            $trimmedAudioFilePath = self::generateTemporaryAudioFilePath("trimmed_$text");

            self::trimAudioFile(
                $originalAudioFilePath,
                $trimmedAudioFilePath
            );

            $finalAudioFilePath = self::createAudioFileMatchingDuration(
                $trimmedAudioFilePath,
                $durations[$index]
            );

            $fs->rename($finalAudioFilePath, $finalAudioFilesFolderPath . DIRECTORY_SEPARATOR . $index . '.mp3');
        }

        return $finalAudioFilesFolderPath;
    }

    public function generateAudioFileForWebVtt(
        string            $webVtt,
        Bcp47LanguageCode $languageCode,
        Gender            $gender
    ): string
    {
        $audioFilesFolderPath = $this->createAudioFilesForWebVttCues(
            $webVtt,
            $languageCode,
            $gender
        );

        $targetFilePath = $audioFilesFolderPath . DIRECTORY_SEPARATOR . 'final.mp3';

        self::concatenateAudioFiles(
            $webVtt,
            $audioFilesFolderPath,
            $targetFilePath
        );

        return $targetFilePath;
    }


    private static function createAudioFileMatchingDuration(
        string $audioFilePath,
        int    $durationInMilliseconds,
    ): string
    {
        $maxTries = 10;
        $currentTry = 0;
        $currentATempo = 1.1;
        $resultingAudioFilePath = $audioFilePath;

        while ($currentTry < $maxTries) {
            $currentTry++;

            if (self::getAudioFileDurationInMilliseconds($resultingAudioFilePath) <= $durationInMilliseconds) {
                return $resultingAudioFilePath;
            }

            $resultingAudioFilePath = self::generateTemporaryAudioFilePath($audioFilePath);
            self::speedupAudioFile(
                $audioFilePath,
                $resultingAudioFilePath,
                $currentATempo
            );

            $currentATempo += 0.1;
        }

        return $resultingAudioFilePath;
    }

    private static function generateTemporaryAudioFilePath(
        string $uniqIdPrefix = ''
    ): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid(md5($uniqIdPrefix), true) . '.mp3';
    }
}
