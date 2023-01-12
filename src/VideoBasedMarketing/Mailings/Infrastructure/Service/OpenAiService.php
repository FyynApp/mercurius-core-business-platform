<?php

namespace App\VideoBasedMarketing\Mailings\Infrastructure\Service;

use Orhanerday\OpenAi\OpenAi;

readonly class OpenAiService
{
    public function improveText(
        string $text,
        string $promptNotes = '',
    ): string|bool
    {
        $openAiApiKey = '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';

        $openAi = new OpenAi($openAiApiKey);

        $text = mb_ereg_replace("\n", ' ', $text);

        $prompt = <<<EOT
Based on the following text, please improve it by making it sound professional and more interesting:

$text
EOT;

        $maxTokens = str_word_count($text) * 2;

        if ($maxTokens > 500) {
            $maxTokens = 500;
        }

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

        return $completionArray['choices'][0]['text'] ?? false;
    }
}
