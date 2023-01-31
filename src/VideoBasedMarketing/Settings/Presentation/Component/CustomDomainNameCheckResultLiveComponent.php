<?php

namespace App\VideoBasedMarketing\Settings\Presentation\Component;

use App\VideoBasedMarketing\Account\Domain\Enum\VotingAttribute;
use App\VideoBasedMarketing\Settings\Domain\Entity\CustomDomainSetting;
use App\VideoBasedMarketing\Settings\Domain\Enum\CustomDomainDnsSetupStatus;
use App\VideoBasedMarketing\Settings\Domain\Enum\CustomDomainHttpSetupStatus;
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
        if (   $this->customDomainSetting->getDnsSetupStatus()  ===  CustomDomainDnsSetupStatus::CheckPositive
            && $this->customDomainSetting->getHttpSetupStatus() === CustomDomainHttpSetupStatus::CheckPositive
        ) {
            return false;
        }

        if (   $this->customDomainSetting->getDnsSetupStatus()  ===  CustomDomainDnsSetupStatus::CheckErrored
            && $this->customDomainSetting->getHttpSetupStatus() === CustomDomainHttpSetupStatus::CheckErrored
        ) {
            return false;
        }

        return true;
    }

    public function getDnsSetupStatus(): CustomDomainDnsSetupStatus
    {
        $this->denyAccessUnlessGranted(
            VotingAttribute::View->value,
            $this->customDomainSetting
        );

        return $this->customDomainSetting->getDnsSetupStatus();
    }

    public function getHttpSetupStatus(): CustomDomainHttpSetupStatus
    {
        $this->denyAccessUnlessGranted(
            VotingAttribute::View->value,
            $this->customDomainSetting
        );

        return $this->customDomainSetting->getHttpSetupStatus();
    }
}
