<?php

namespace App\VideoBasedMarketing\LingoSync\Infrastructure\Service;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\Shared\Domain\Enum\Gender;
use App\VideoBasedMarketing\LingoSync\Infrastructure\ApiClient\GoogleCloudTextToSpeechApiClient;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Infrastructure\Enum\AssetMimeType;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Exception;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;


readonly class LingoSyncInfrastructureService
{
    public function __construct(
        private GoogleCloudTextToSpeechApiClient $googleCloudTextToSpeechApiClient,
        private RecordingsInfrastructureService  $recordingsInfrastructureService
    )
    {
    }

    public static function cleanupPseudoSentencesInWebVtt(
        string $webvtt,
        array  $abbreviations
    ): string
    {
        // Split the input into cues
        $cues = explode("\n\n", trim($webvtt));

        // Remove the "WEBVTT" header
        array_shift($cues);

        // Initialize the cleaned up cues
        $cleanedUpCues = [];

        // Iterate over the cues
        for ($i = 0; $i < count($cues); $i++) {
            // Split the cue into lines
            $lines = explode("\n", $cues[$i]);

            // Extract the timestamp and text
            $timestamp = $lines[1];
            $text = implode(' ', array_slice($lines, 2));

            // Check if the last word of the text matches the first part of any abbreviation
            foreach ($abbreviations as $abbreviation) {
                $parts = explode('.', $abbreviation);
                if (   str_ends_with(mb_strtolower(trim($text)), ' ' . mb_strtolower($parts[0]) . '.')
                    && isset($cues[$i + 1])
                    && str_starts_with(mb_strtolower(explode("\n", $cues[$i + 1])[2]), mb_strtolower($parts[1]) . '.')
                ) {
                    // Concatenate the next cue to the current one and remove the next cue
                    $nextCueLines = explode("\n", $cues[$i + 1]);
                    $nextCueText = implode(' ', array_slice($nextCueLines, 2));
                    $nextCueTimestamp = $nextCueLines[1];
                    $text .= $nextCueText;
                    $timestamp = explode(' --> ', $timestamp)[0] . ' --> ' . explode(' --> ', $nextCueTimestamp)[1];
                    array_splice($cues, $i + 1, 1);
                    break;
                }
            }

            // Update the cue
            $lines[1] = $timestamp;
            $lines[2] = $text;

            // Remove the cue number
            array_splice($lines, 0, 1);

            // Add the cue to the cleaned up cues
            $cleanedUpCues[] = implode("\n", $lines);
        }

        // Build the output
        $output = "WEBVTT\n\n";
        foreach ($cleanedUpCues as $index => $cue) {
            $output .= ($index + 1) . "\n" . $cue . "\n\n";
        }

        return trim($output);
    }

    public static function compactizeWebVtt(string $webvtt): string
    {
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

            if (sizeof($lines) < 3 || $lines[0] === 'WEBVTT') {
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

        $output = trim($output);

        $output = self::cleanupPseudoSentencesInWebVtt(
            $output,
            [
                'z.b.',
                'u.a..',
                'e.g.',
                'e.a.',
            ]
        );

        return trim($output);
    }

    public static function mapWebVttTimestamps(
        string $webvttWithCorrectTimestamps,
        string $webvttWithCorrectTexts
    ): string
    {
        // Split the inputs into cues
        $cues1 = explode("\n\n", trim($webvttWithCorrectTimestamps));
        $cues2 = explode("\n\n", trim($webvttWithCorrectTexts));

        // Remove the "WEBVTT" headers
        array_shift($cues1);
        array_shift($cues2);

        // Initialize the mapped cues
        $mappedCues = [];

        // Iterate over the cues
        for ($i = 0; $i < count($cues1); $i++) {
            // Split the cues into lines
            $lines1 = explode("\n", $cues1[$i]);
            $lines2 = explode("\n", $cues2[$i]);

            // Extract the timestamp from the first cue and the text from the second cue
            $timestamp = $lines1[1];
            $text = implode(' ', array_slice($lines2, 2));

            // Create the mapped cue
            $mappedCue = ($i + 1) . "\n" . $timestamp . "\n" . $text;

            // Add the mapped cue to the mapped cues
            $mappedCues[] = $mappedCue;
        }

        // Build the output
        $output = "WEBVTT\n\n" . implode("\n\n", $mappedCues);

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

    public static function audioFileIsUsable(
        string $audioFilePath
    ): bool
    {
        $command = 'ffmpeg -i ' . $audioFilePath . ' -f null - 2>&1 | grep "Input #0"';
        $output = shell_exec($command);

        if (is_null($output)) {
            return false;
        }

        return true;
    }

    public static function getAudioFileDurationInMilliseconds(
        string $audioFilePath
    ): ?int
    {
        $output = shell_exec(
            'ffmpeg -i ' . $audioFilePath . ' -f null - 2>&1 | grep Duration | cut -d " " -f 4 | sed s/,//'
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

    public static function millisecondsToTimestamp(int $milliseconds): string {
        $seconds = floor($milliseconds / 1000);
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);

        // remaining seconds after minutes are subtracted
        $seconds = $seconds % 60;

        // remaining minutes after hours are subtracted
        $minutes = $minutes % 60;

        // remaining milliseconds after seconds are subtracted
        $remainingMilliseconds = $milliseconds % 1000;

        // format the result
        return sprintf('%02d:%02d:%02d.%03d', $hours, $minutes, $seconds, $remainingMilliseconds);
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

    public static function getWebVttDurationsAsMilliseconds(string $webVtt): array
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

    public static function concatenateAudioFiles(
        string  $webVtt,
        string  $sourceFilesFolderPath,
        ?string $targetFilePath = null
    ): string
    {
        if (is_null($targetFilePath)) {
            $targetFilePath = sys_get_temp_dir()
                . DIRECTORY_SEPARATOR
                . uniqid(md5($webVtt), true)
                . '.'
                . RecordingsInfrastructureService::mimeTypeToFileSuffix(
                    AssetMimeType::AudioMpeg
                );
        }

        $starts = self::getWebVttStartsAsMilliseconds($webVtt);
        $durations = self::getWebVttDurationsAsMilliseconds($webVtt);
        $texts = self::getWebVttTexts($webVtt);

        $files = [];
        $filter = '';
        $previousEnd = 0;
        $audioIndex = 0;

        $milliseconds = 0;

        foreach ($starts as $index => $start) {
            $silenceDuration = max(0, $start - $previousEnd) / 1000; // Duration in seconds

            if ($silenceDuration > 0) {
                // Generate a silence audio file of the needed duration
                $silenceFilePath = "/{$sourceFilesFolderPath}/silence_{$index}.mp3";

                // silenceDuration tends to be a bit too short...
                $silenceDuration *= 1.25;

                exec("ffmpeg -y -f lavfi -i anullsrc=channel_layout=stereo:sample_rate=44100 -t {$silenceDuration} {$silenceFilePath}");


                echo "[" . self::millisecondsToTimestamp($milliseconds) . "] [" . self::millisecondsToTimestamp($start) . "] Adding {$silenceDuration} seconds of silence before " . self::millisecondsToTimestamp($start) . " via file {$silenceFilePath}\n";
                $milliseconds += $silenceDuration * 1000;

                $files[] = $silenceFilePath;
                $filter .= "[{$audioIndex}:a]";
                $audioIndex++;
            }

            $filter .= "[{$audioIndex}:a]";
            $audioIndex++;

            if (self::audioFileIsUsable("{$sourceFilesFolderPath}/{$index}.mp3")) {
                echo "[" . self::millisecondsToTimestamp($milliseconds) . "] [" . self::millisecondsToTimestamp($start) . "] Adding {$sourceFilesFolderPath}/{$index}.mp3 with text '{$texts[$index]}' at " . self::millisecondsToTimestamp($start) . "\n";
                $milliseconds += self::getAudioFileDurationInMilliseconds("{$sourceFilesFolderPath}/{$index}.mp3");
                $files[] = "{$sourceFilesFolderPath}/{$index}.mp3";
                $previousEnd = $start + self::getAudioFileDurationInMilliseconds("{$sourceFilesFolderPath}/{$index}.mp3");
            } else {
                $silenceDuration = $durations[$index] / 1000;
                $silenceFilePath = "/{$sourceFilesFolderPath}/silence_{$index}_fix_for_unusable_audio.mp3";
                exec("ffmpeg -y -f lavfi -i anullsrc=channel_layout=stereo:sample_rate=44100 -t {$silenceDuration} {$silenceFilePath}");

                echo "[" . self::millisecondsToTimestamp($milliseconds) . "] [" . self::millisecondsToTimestamp($start) . "] Adding {$silenceDuration} seconds of silence at " . self::millisecondsToTimestamp($start) . " because file {$sourceFilesFolderPath}/{$index}.mp3 for text '{$texts[$index]}' is unusable\n";
                $milliseconds += $silenceDuration * 1000;
                $files[] = $silenceFilePath;
                $previousEnd = $start + $durations[$index];
            }
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

        return $targetFilePath;
    }


    /**
     * @throws ValidationException
     * @throws ApiException
     * @throws Exception
     */
    public function createAudioFilesForWebVttCues(
        string            $webVtt,
        Bcp47LanguageCode $languageCode,
        Gender            $gender
    ): string
    {
        $texts = self::getWebVttTexts($webVtt);
        $starts = self::getWebVttStartsAsMilliseconds($webVtt);
        $durations = self::getWebVttDurationsAsMilliseconds($webVtt);

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
                $durations[$index],
                $starts[$index],
                $index,
                $text
            );

            echo "Copying {$finalAudioFilePath} to {$finalAudioFilesFolderPath}/{$index}.mp3\n";
            $fs->copy($finalAudioFilePath, $finalAudioFilesFolderPath . DIRECTORY_SEPARATOR . $index . '.mp3');
        }

        return $finalAudioFilesFolderPath;
    }

    /**
     * @throws ValidationException
     * @throws ApiException
     */
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
        int    $allowedDurationInMilliseconds,
        int    $startInMilliseconds,
        int    $index,
        string $text
    ): string
    {
        $maxTries = 10;
        $currentTry = 0;
        $currentATempo = 1.1;
        $resultingAudioFilePath = $audioFilePath;

        while ($currentTry < $maxTries) {

            echo "Try $currentTry with a tempo of $currentATempo for text '$text' with index $index starting at $startInMilliseconds, with an allowed duration of $allowedDurationInMilliseconds\n";

            $currentTry++;

            if (!self::audioFileIsUsable($resultingAudioFilePath)) {
                echo "Audio file unusable\n";
                return $audioFilePath;
            }

            $durationInMilliseconds = self::getAudioFileDurationInMilliseconds($resultingAudioFilePath);

            echo "Duration is $durationInMilliseconds\n";

            if ($durationInMilliseconds <= $allowedDurationInMilliseconds) {
                echo "Duration matches, returning $resultingAudioFilePath\n";
                return $resultingAudioFilePath;
            }

            echo "Duration is too long\n";

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

    /**
     * @throws Exception
     */
    public function createVideoFileFromVideoAndAudioFile(
        Video  $video,
        string $audioFilePath
    ): string
    {
        if ($video->hasAssetFullMp4()) {
            $videoMimeType = AssetMimeType::VideoMp4;
        } elseif ($video->hasAssetFullWebm()) {
            $videoMimeType = AssetMimeType::VideoWebm;
        } else {
            throw new Exception(
                'Need video with either '
                . AssetMimeType::VideoMp4->value
                . ' or '
                . AssetMimeType::VideoWebm->value
                . ' asset'
            );
        }

        $targetFilePath = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . uniqid(md5($video->getId()), true)
            . '.'
            . RecordingsInfrastructureService::mimeTypeToFileSuffix(
                $videoMimeType
            )
        ;

        $process = new Process(
            [
                'ffmpeg',

                '-i',
                $this
                    ->recordingsInfrastructureService
                    ->getVideoFullAssetFilePath(
                        $video,
                        $videoMimeType
                    ),

                '-i',
                $audioFilePath,

                '-c:v',
                'copy',

                '-map',
                '0:v:0',

                '-map',
                '1:a:0',

                '-y',
                $targetFilePath
            ]
        );
        $process->setTimeout(60 * 2);
        $process->run();

        return $targetFilePath;
    }
}
