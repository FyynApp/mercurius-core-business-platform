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

        #throw new \Exception("API KEY: $openAiApiKey");

        $openAi = new OpenAi($openAiApiKey);

        $prompt = <<<EOT

A user is writing an email to a potential customer.
Please optimize the text following the keyword TEXTSTARTSHERE. Do not include the keyword TEXTSTARTSHERE in the result.
Make it more persuasive and convincing, and make sure that the language is correct and professional.
Do not keep the tonality of the original text. Instead, make it as professional and perfect as possible.
Provide only the optimized text, and no explanations or other text. Do not put any placeholders in brackets into the resulting text.
$promptNotes

TEXTSTARTSHERE
$text
EOT;

        $completion = $openAi->completion([
            'model' => 'text-davinci-003',
            'prompt' => $prompt,
            'temperature' => 0.5,
            'max_tokens' => 100,
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0,
        ]);

        $completionArray = json_decode($completion, true);

        return $completionArray['choices'][0]['text'] ?? false;
    }
}
