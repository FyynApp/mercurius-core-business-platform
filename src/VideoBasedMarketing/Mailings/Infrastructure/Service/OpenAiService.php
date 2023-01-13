<?php

namespace App\VideoBasedMarketing\Mailings\Infrastructure\Service;

use Exception;
use Orhanerday\OpenAi\OpenAi;

readonly class OpenAiService
{
    /**
     * @throws Exception
     */
    public function improveText(
        string $text,
        string $promptNotes = '',
    ): string|bool
    {
        $openAiApiKey = $_ENV['OPENAI_API_KEY'];

        $openAi = new OpenAi($openAiApiKey);

        $text = mb_ereg_replace("\n", ' ', $text);

        $prompt = <<<EOT
Based on the following text, please improve it by making it sound professional and more interesting, while keeping it concise and to the point:

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

        return $completionArray['choices'][0]['text'] ?? throw new Exception("Improvement failed: $completion");
    }
}
