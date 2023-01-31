<?php

namespace App\VideoBasedMarketing\Settings\Presentation\Component;

use App\VideoBasedMarketing\Account\Domain\Enum\VotingAttribute;
use App\VideoBasedMarketing\Settings\Domain\Entity\CustomDomainSetting;
use App\VideoBasedMarketing\Settings\Domain\Enum\CustomDomainDnsSetupStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    'videobasedmarketing_settings_custom_domain_name_check_result',
    '@videobasedmarketing.settings/custom_domain_name_check_result_live_component.html.twig'
)]
class CustomDomainNameCheckResultLiveComponent
    extends AbstractController
{
    use DefaultActionTrait;


    #[LiveProp]
    public CustomDomainSetting $customDomainSetting;

    public function shouldPoll(): bool
    {
        return $this->customDomainSetting->getDnsSetupStatus() !== CustomDomainDnsSetupStatus::CheckPositive
            && $this->customDomainSetting->getDnsSetupStatus() !== CustomDomainDnsSetupStatus::CheckNegative
            && $this->customDomainSetting->getDnsSetupStatus() !== CustomDomainDnsSetupStatus::CheckErrored;
    }

    public function getStatus(): CustomDomainDnsSetupStatus
    {
        $this->denyAccessUnlessGranted(
            VotingAttribute::View->value,
            $this->customDomainSetting
        );

        return $this->customDomainSetting->getDnsSetupStatus();
    }
}
