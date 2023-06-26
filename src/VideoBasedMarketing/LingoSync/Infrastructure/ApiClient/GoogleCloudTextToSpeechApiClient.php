<?php

namespace App\VideoBasedMarketing\LingoSync\Infrastructure\ApiClient;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\Shared\Domain\Enum\Gender;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;

readonly class GoogleCloudTextToSpeechApiClient
{
    /**
     * @throws ValidationException
     * @throws ApiException
     */
    public function createAudioFileFromText(
        string            $text,
        Bcp47LanguageCode $languageCode,
        Gender            $gender,
        float             $speakingRate,
        string            $audioFilePath
    ): void
    {
        $textToSpeechClient = new TextToSpeechClient(
            ['credentials' => $this->getCredentials()]
        );

        $input = new SynthesisInput();
        $input->setText($text);

        $voice = new VoiceSelectionParams();
        $voice->setLanguageCode($languageCode->value);
        $voice->setName($this->getVoiceName($languageCode, $gender));

        $audioConfig = new AudioConfig();
        $audioConfig->setAudioEncoding(AudioEncoding::LINEAR16);
        $audioConfig->setSpeakingRate($speakingRate);

        $resp = $textToSpeechClient->synthesizeSpeech($input, $voice, $audioConfig);
        file_put_contents($audioFilePath, $resp->getAudioContent());
    }

    public function getCredentials(): array
    {
        return [
            'type' => 'service_account',
            'project_id' => 'lucky-trail-388810',
            'private_key_id' => '4ad704c517d1aefcea045dcaa8ee0b5ead3d7934',
            'private_key' => $_ENV['GOOGLE_CLOUD_TEXT_TO_SPEECH_API_PRIVATE_KEY'],
            'client_email' => 'text-to-speech-fyyn-preproddev@lucky-trail-388810.iam.gserviceaccount.com',
            'client_id' => '103366342825442163192',
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
            'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
            'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/text-to-speech-fyyn-preproddev%40lucky-trail-388810.iam.gserviceaccount.com',
            'universe_domain' => 'googleapis.com'

        ];
    }

    public function getVoiceName(
        Bcp47LanguageCode $languageCode,
        Gender            $gender
    ): string
    {
        return match ($languageCode) {
            Bcp47LanguageCode::EnUs => match ($gender) {
                Gender::Male => 'en-US-Wavenet-B',
                Gender::Female => 'en-US-Wavenet-C',
            },

            Bcp47LanguageCode::DeDe => match ($gender) {
                Gender::Male => 'de-DE-Wavenet-B',
                Gender::Female => 'de-DE-Wavenet-A',
            },

            Bcp47LanguageCode::NlNl => match ($gender) {
                Gender::Male => 'nl-NL-Wavenet-B',
                Gender::Female => 'nl-NL-Wavenet-A',
            },

            Bcp47LanguageCode::EsEs => match ($gender) {
                Gender::Male => 'es-ES-Wavenet-B',
                Gender::Female => 'es-ES-Wavenet-C',
            },

            Bcp47LanguageCode::FrFr => match ($gender) {
                Gender::Male => 'fr-FR-Wavenet-B',
                Gender::Female => 'fr-FR-Wavenet-A',
            },

            Bcp47LanguageCode::ItIt => match ($gender) {
                Gender::Male => 'it-IT-Wavenet-C',
                Gender::Female => 'it-IT-Wavenet-A',
            },

            Bcp47LanguageCode::TrTr => match ($gender) {
                Gender::Male => 'tr-TR-Wavenet-B',
                Gender::Female => 'tr-TR-Wavenet-A',
            },

            Bcp47LanguageCode::PtPt => match ($gender) {
                Gender::Male => 'pt-PT-Wavenet-B',
                Gender::Female => 'pt-PT-Wavenet-A',
            },

            Bcp47LanguageCode::PtBr => match ($gender) {
                Gender::Male => 'pt-BR-Wavenet-B',
                Gender::Female => 'pt-BR-Wavenet-A',
            },

            Bcp47LanguageCode::PlPl => match ($gender) {
                Gender::Male => 'pl-PL-Wavenet-B',
                Gender::Female => 'pl-PL-Wavenet-A',
            },

            Bcp47LanguageCode::BnBd, Bcp47LanguageCode::BnIn => match ($gender) {
                Gender::Male => 'bn-IN-Wavenet-B',
                Gender::Female => 'bn-IN-Wavenet-A',
            },

            Bcp47LanguageCode::HiIn => match ($gender) {
                Gender::Male => 'hi-IN-Wavenet-B',
                Gender::Female => 'hi-IN-Wavenet-A',
            },

            Bcp47LanguageCode::CmnHansCn => match ($gender) {
                Gender::Male => 'cmn-CN-Wavenet-B',
                Gender::Female => 'cmn-CN-Wavenet-A',
            },

            Bcp47LanguageCode::RuRu => match ($gender) {
                Gender::Male => 'ru-RU-Wavenet-B',
                Gender::Female => 'ru-RU-Wavenet-A',
            },

            Bcp47LanguageCode::HeIl => match ($gender) {
                Gender::Male => 'he-IL-Wavenet-B',
                Gender::Female => 'he-IL-Wavenet-A',
            },
        };
    }
}
