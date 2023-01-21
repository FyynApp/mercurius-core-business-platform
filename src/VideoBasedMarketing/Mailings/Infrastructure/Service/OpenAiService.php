<?php

namespace App\VideoBasedMarketing\Mailings\Infrastructure\Service;

use App\Shared\Domain\Enum\Iso639_1Code;
use App\VideoBasedMarketing\Mailings\Domain\Entity\VideoMailing;
use Exception;
use Orhanerday\OpenAi\OpenAi;
use ValueError;

readonly class OpenAiService
{
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
            'model' => 'text-davinci-003',
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
}
