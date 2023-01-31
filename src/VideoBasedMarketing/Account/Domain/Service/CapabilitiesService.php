<?php

namespace App\VideoBasedMarketing\Account\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\Capability;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipService;
use App\VideoBasedMarketing\Settings\Domain\Service\SettingsDomainService;


readonly class CapabilitiesService
{
    public function __construct(
        private MembershipService $membershipService
    )
    {}

    public function canOpenRecordingStudio(User $user): bool
    {
        return  $user->isRegistered()
            &&  $user->isVerified()
            && !$user->isExtensionOnly();
    }

    public function canUploadVideos(User $user): bool
    {
        return  $user->isRegistered()
            &&  $user->isVerified();
    }

    public function canEditVideos(User $user): bool
    {
        return  $user->isRegistered()
            &&  $user->isVerified()
            && !$user->isExtensionOnly();
    }

    public function canSendVideoMailing(User $user): bool
    {
        return  $user->isRegistered()
            &&  $user->isVerified();
    }

    public function canSeeLeftNavigation(?User $user): bool
    {
        return !is_null($user);
    }

    public function canSeeTopNavigationOnLargeScreenWidth(?User $user): bool
    {
        return !is_null($user);
    }

    public function canSeeUserInfoInNavigation(?User $user): bool
    {
        return !is_null($user) && $user->isRegistered();
    }

    public function canSeeProfileDropdownInSideNavigation(?User $user): bool
    {
        return !is_null($user) && $user->isRegistered();
    }

    public function canSeeOwnProfilePhoto(?User $user): bool
    {
        return !is_null($user) && $user->hasProfilePhoto();
    }

    public function canSeeOwnProfileName(?User $user): bool
    {
        return !is_null($user)
            && (
                   !is_null($user->getFirstName())
                || !is_null($user->getLastName())
            );
    }

    public function canSeeFooterOnFullPage(?User $user): bool
    {
        return is_null($user);
    }

    public function canSeeVideoOnlyPresentationpageTemplateTitle(User $user): bool
    {
        return !$user->isExtensionOnly();
    }

    public function canSeeVideoTitle(User $user): bool
    {
        return !$user->isExtensionOnly();
    }

    public function mustBeForcedToClaimUnregisteredUser(User $user): bool
    {
        return !$user->isRegistered();
    }

    public function canBeAskedToUseExtension(User $user): bool
    {
        return $user->isExtensionOnly();
    }

    public function canHaveAllVideoAssetsGenerated(User $user): bool
    {
        return $user->isRegistered()
            && $user->isVerified();
    }

    public function canAdministerVideos(User $user): bool
    {
        return $user->isAdmin();
    }

    public function canUseCustomDomainForLandingpages(User $user): bool
    {
        return $this->hasCapability($user, Capability::CustomDomain);
    }

    public function hasCapability(
        ?User      $user,
        Capability $capability
    ): bool
    {
        if (is_null($user)) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $this
            ->membershipService
            ->getCurrentlySubscribedMembershipPlanForUser($user)
            ->hasCapability($capability);
    }
}
