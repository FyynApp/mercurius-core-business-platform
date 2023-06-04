<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\Enum;


enum LingoSyncProcessTaskType: string
{
    case GenerateOriginalLanguageTranscription = 'generateOriginalLanguageTranscription';
    case WaitForTranslation = 'waitForTranslation';
    case CreateAudioSnippet = 'createAudioSnippet';
    case ConcatenateAudioSnippets = 'concatenateAudioSnippets';
    case GenerateTranslatedVideo = 'generateTranslatedVideo';
}
