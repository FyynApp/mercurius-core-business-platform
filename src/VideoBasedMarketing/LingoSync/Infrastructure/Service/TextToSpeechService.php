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

    public function trimAudioFile(
        string $sourceAudioFilePath,
        string $targetAudioFilePath
    ): void
    {
        // ffmpeg -i var/lingosync-test/0.mp3 -af "silenceremove=start_periods=1:start_duration=1:start_threshold=-60dB:detection=peak,areverse,silenceremove=start_periods=1:start_duration=1:start_threshold=-60dB:detection=peak,areverse" var/lingosync-test/0.trimmed.mp3
    }

    public function speedupAudioFile(
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

    public static function getWebVttStarts(string $webVtt): array
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

    public static function concatenateAudioFiles(string $webVtt, string $sourceFilesFolderPath, string $targetFilePath): void
    {
        $starts = self::getWebVttStarts($webVtt);
        $durations = self::getWebVttDurations($webVtt);

        $files = [];
        $filter = '';
        $previousEnd = 0;
        $audioIndex = 0;

        foreach ($starts as $index => $start) {
            $silenceDuration = max(0, $start - $previousEnd) / 1000; // Duration in seconds

            echo "\nSilence duration before $start: {$silenceDuration} - previousEnd is $previousEnd\n";

            if ($silenceDuration > 0) {
                // Generate a silence audio file of the needed duration
                $silenceFile = sys_get_temp_dir() . '/' . uniqid('silence_', true) . '.mp3';
                exec("ffmpeg -y -f lavfi -i anullsrc=channel_layout=stereo:sample_rate=44100 -t {$silenceDuration} {$silenceFile}");

                $files[] = $silenceFile;
                $filter .= "[{$audioIndex}:a]";
                $audioIndex++;
            }

            $files[] = "{$sourceFilesFolderPath}/{$index}.mp3";
            $filter .= "[{$audioIndex}:a]";
            $audioIndex++;

            $previousEnd = $start + $durations[$index];
        }

        $cmd = "ffmpeg -y -i " . implode(' -i ', $files) . " -filter_complex '{$filter}concat=n={$audioIndex}:v=0:a=1[out]' -map '[out]' {$targetFilePath}";

        echo $cmd;

        // Execute the command
        exec($cmd);

        // Delete the temporary silence audio files
        foreach ($files as $file) {
            if (strpos($file, 'silence_') !== false) {
                unlink($file);
            }
        }
    }


    public function createAudiosForWebVtt(string $webVtt): void
    {
        // Texte holen
        // Durations holen
        // Iterieren über Texte
            // Audiofile erstellen
            // Audiofile trimmen
            // Länge messen
            // Wenn zu lang, dann speedup - wiederholen bis es passt
            // Audiofile speichern
    }
}
