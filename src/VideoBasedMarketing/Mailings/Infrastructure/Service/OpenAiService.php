<?php

namespace App\VideoBasedMarketing\Mailings\Infrastructure\Service;

use App\Shared\Domain\Enum\Iso639_1Code;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionWebVtt;
use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Service\WebVttParserService;
use App\VideoBasedMarketing\Mailings\Domain\Entity\VideoMailing;
use Exception;
use Orhanerday\OpenAi\OpenAi;
use ValueError;

readonly class OpenAiService
{
    public function __construct(
        private WebVttParserService $webVttParserService
    )
    {
    }

    /**
     * @throws Exception
     */
    public function complete(
        string $prompt
    ): string
    {
        $openAiApiKey = $_ENV['OPENAI_API_KEY'];

        $openAi = new OpenAi($openAiApiKey);

        $completion = $openAi->completion([
            'model' => 'gpt-3.5-turbo-instruct',
            'prompt' => $prompt,
            'temperature' => 0.5,
            'max_tokens' => 1796,
            'top_p' => 1.0,
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0,
        ]);

        $completionArray = json_decode($completion, true);

        return $completionArray['choices'][0]['text'] ?? throw new Exception("Improvement failed: $completion");
    }

    /**
     * @throws Exception
     */
    public function improveTextForVideoMailing(
        VideoMailing $videoMailing,
        string $promptNotes = '',
    ): string|bool
    {
        $openAiApiKey = $_ENV['OPENAI_API_KEY'];

        $openAi = new OpenAi($openAiApiKey);

        $text = mb_ereg_replace("\n", ' ', $videoMailing->getBodyAboveVideo());

        if (   is_null($videoMailing->getUser()->getUiLanguageCode())
            || $videoMailing->getUser()->getUiLanguageCode() === Iso639_1Code::En
        ) {
            $prompt = <<<EOT
Based on the following text, please improve it by making it sound professional and more interesting, while keeping it concise and to the point, and without translating it into another language:

$text
EOT;
        } elseif (
            $videoMailing->getUser()->getUiLanguageCode() === Iso639_1Code::De
        ) {
            $prompt = <<<EOT
Bitte optimiere den folgenden Text, indem du ihn professioneller und interessanter formulierst, während du ihn dabei kurz und griffig belässt, und ohne ihn in eine andere Sprache zu übersetzen:

$text
EOT;
        } else {
            throw new ValueError("Cannot handle ui language code '{$videoMailing->getUser()->getUiLanguageCode()->value}'.");
        }


        $maxTokens = (int)(mb_strlen($text) / 4);

        if ($maxTokens > 2000) {
            $maxTokens = 2000;
        }

        $maxTokens = 2000;

        $completion = $openAi->completion([
            'model' => 'gpt-3.5-turbo-instruct',
            'prompt' => $prompt,
            'temperature' => 0.5,
            'max_tokens' => $maxTokens,
            'top_p' => 1.0,
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0,
        ]);

        $completionArray = json_decode($completion, true);

        return $completionArray['choices'][0]['text'] ?? throw new Exception("Improvement failed: $completion");
    }

    /**
     * @throws Exception
     */
    public function summarizeWebVtt(
        AudioTranscriptionWebVtt $webVtt
    ): string|bool
    {
        $openAiApiKey = $_ENV['OPENAI_API_KEY'];

        $openAi = new OpenAi($openAiApiKey);

        if ($webVtt->getBcp47LanguageCode() === Bcp47LanguageCode::DeDe) {
            $prompt = <<<EOT
Bitte fasse den folgenden Text zusammen, und antworte ohne Einleitung oder Kommentare, nur mit der Zusammenfassung selbst:

{$this->webVttParserService->getText($webVtt)}
EOT;
        } elseif (
            $webVtt->getBcp47LanguageCode() === Bcp47LanguageCode::EnUs
        ) {
            $prompt = <<<EOT
Please create a summary of the following text, and in your response, do not add an introduction or any other comments, only respond with the summary itself:

{$this->webVttParserService->getText($webVtt)}
EOT;
        } else {
            throw new ValueError(
                "Cannot handle language code '{$webVtt->getBcp47LanguageCode()->value}'."
            );
        }


        $maxTokens = (int)(mb_strlen($prompt) / 4);

        if ($maxTokens > 2000) {
            $maxTokens = 2000;
        }

        $maxTokens = 2000;

        $completion = $openAi->completion([
            'model' => 'gpt-3.5-turbo-instruct',
            'prompt' => $prompt,
            'temperature' => 0.5,
            'max_tokens' => $maxTokens,
            'top_p' => 1.0,
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0,
        ]);

        $completionArray = json_decode($completion, true);

        return $completionArray['choices'][0]['text']
            ?? throw new Exception("Improvement failed: $completion");
    }
}
