<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\Enum;


enum LingoSyncProcessTaskType: string
{
    case GenerateOriginalLanguageTranscription = 'generateOriginalLanguageTranscription';
    case TranslateTranscription = 'translateTranscription';
    case CreateAudioSnippet = 'createAudioSnippet';
    case ConcatenateAudioSnippets = 'concatenateAudioSnippets';
    case GenerateTranslatedVideo = 'generateTranslatedVideo';
}
