<?php

namespace App\VideoBasedMarketing\Settings\Infrastructure\Message;

use App\Shared\Infrastructure\Message\AsyncMessageInterface;
use App\VideoBasedMarketing\Settings\Domain\Entity\CustomDomainSetting;
use ValueError;

class CheckCustomDomainNameCommandMessage
    implements AsyncMessageInterface
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
