<?php

namespace App\VideoBasedMarketing\Settings\Infrastructure\SymfonyMessage;

use App\Shared\Infrastructure\SymfonyMessage\AsyncSymfonyMessageInterface;
use App\VideoBasedMarketing\Settings\Domain\Entity\CustomDomainSetting;
use ValueError;

class CheckCustomDomainNameSetupCommandSymfonyMessage
    implements AsyncSymfonyMessageInterface
{
    private string $customDomainSettingId;

    public function __construct(
        CustomDomainSetting $customDomainSetting
    )
    {
        if (is_null($customDomainSetting->getId())) {
            throw new ValueError('customDomainSetting needs an id.');
        }
        $this->customDomainSettingId = $customDomainSetting->getId();
    }

    public function getCustomDomainSettingId(): string
    {
        return $this->customDomainSettingId;
    }
}
