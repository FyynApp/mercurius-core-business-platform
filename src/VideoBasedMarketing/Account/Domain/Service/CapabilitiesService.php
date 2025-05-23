<?php

namespace App\VideoBasedMarketing\Account\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Membership\Domain\Enum\Capability;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipPlanService;
use App\VideoBasedMarketing\Organization\Domain\Enum\AccessRight;
use App\VideoBasedMarketing\Organization\Domain\Service\OrganizationDomainService;


readonly class CapabilitiesService
{
    public function __construct(
        private MembershipPlanService     $membershipPlanService,
        private OrganizationDomainService $organizationDomainService
    )
    {
    }

    public function canSubscribeToMembershipPlans(User $user): bool
    {
        return $user->ownsCurrentlyActiveOrganization();
    }

    public function canPurchasePackages(User $user): bool
    {
        return $user->ownsCurrentlyActiveOrganization();
    }

    public function canOpenRecordingStudio(User $user): bool
    {
        return false;
    }

    public function canUploadVideos(User $user): bool
    {
        return true;
    }

    public function canRecordVideosWithNativeBrowserRecorder(): bool
    {
        return true;
    }

    public function canEditVideos(User $user): bool
    {
        return $user->isRegistered()
            && $user->isVerified();
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
        return false;
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

    public function canPresentLandingpageOnCustomDomain(User $user): bool
    {
        return $this->hasCapability($user, Capability::CustomDomain);
    }

    public function canEditOrganizationName(User $user): bool
    {
        return $this->organizationDomainService->userHasAccessRight(
            $user,
            AccessRight::EDIT_ORGANIZATION_NAME
        );
    }

    public function canEditCustomDomainSetting(User $user): bool
    {
        return $this->organizationDomainService->userHasAccessRight(
            $user,
            AccessRight::EDIT_CUSTOM_DOMAIN_SETTINGS
        );
    }


    public function canPresentOwnLogoOnLandingpage(User $user): bool
    {
        return $this->hasCapability($user, Capability::CustomLogoOnLandingpage);
    }

    public function canEditCustomLogoSetting(User $user): bool
    {
        return $this->organizationDomainService->userHasAccessRight(
            $user,
            AccessRight::EDIT_CUSTOM_LOGO_SETTINGS
        );
    }

    public function canPresentAdFreeLandingpage(User $user): bool
    {
        return $this->hasCapability($user, Capability::AdFreeLandingpages);
    }

    public function canManuallyStartAudioTranscription(User $user): bool
    {
        return $this
            ->membershipPlanService
            ->getSubscribedMembershipPlanForCurrentlyActiveOrganization(
                $user
            )->mustBeBought();
    }

    public function canTranslateVideos(User $user): bool
    {
        /*
        if (str_ends_with($user->getEmail(), '@kiessling.net')
            || str_ends_with($user->getEmail(), '@smart-dsgvo.de')
            || str_ends_with($user->getEmail(), '@maik-becker.de')
            || str_ends_with($user->getEmail(), '@fyyn.io')
        ) {
            return true;
        }*/

        return $this->hasCapability($user, Capability::VideoTranslation);
    }

    public function canInviteOrganizationMembers(User $user): bool
    {
        return $this->organizationDomainService->userHasAccessRight(
            $user,
            AccessRight::INVITE_ORGANIZATION_MEMBERS
        );
    }

    public function canSeeOrganizationGroupsAndMembers(User $user): bool
    {
        return $this->organizationDomainService->userHasAccessRight(
            $user,
            AccessRight::SEE_ORGANIZATION_GROUPS_AND_MEMBERS
        );
    }

    public function canMoveOrganizationMembersIntoGroups(User $user): bool
    {
        return $this->organizationDomainService->userHasAccessRight(
            $user,
            AccessRight::MOVE_ORGANIZATION_MEMBERS_INTO_GROUPS
        );
    }

    public function canShowRecordingRequests(User $user): bool
    {
        return true;
    }

    public function canCreateRecordingRequests(User $user): bool
    {
        return true;
    }


    public function canEditFolderVisibilityForNonAdministrators(User $user): bool
    {
        return $this->organizationDomainService->userHasAccessRight(
            $user,
            AccessRight::EDIT_FOLDER_VISIBILITY_FOR_NON_ADMINISTRATORS
        );
    }


    public function canSeeFoldersNotVisibleForNonAdministrators(User $user): bool
    {
        return $this->canEditFolderVisibilityForNonAdministrators($user);
    }

    public function canDefineDefaultFolderForAdministratorRecordings(User $user): bool
    {
        return $this->organizationDomainService->userHasAccessRight(
            $user,
            AccessRight::DEFINE_DEFAULT_FOLDER_FOR_ADMINISTRATOR_RECORDINGS
        );
    }

    public function canStoreNewRecordingsInDefaultFolderForAdministratorRecordings(User $user): bool
    {
        return $this->organizationDomainService->userHasAccessRight(
            $user,
            AccessRight::CAN_STORE_NEW_RECORDINGS_IN_DEFAULT_FOLDER_FOR_ADMINISTRATOR_RECORDINGS
        );
    }

    public function getMaxRecordingTimeInSeconds(
        User $user
    ): int
    {
        if ($user->isAdmin()) {
            return 60 * 60;
        }

        $plan = $this
            ->membershipPlanService
            ->getSubscribedMembershipPlanForCurrentlyActiveOrganization($user);

        return $plan->getMaxRecordingTimeInSeconds();
    }

    public function getMaxVideoUploadFilesizeInBytes(
        User $user
    ): int
    {
        if ($user->isAdmin()) {
            return 2684354560; // 2.5 GiB
        }

        $plan = $this
            ->membershipPlanService
            ->getSubscribedMembershipPlanForCurrentlyActiveOrganization($user);

        return $plan->getMaxVideoUploadFilesizeInBytes();
    }

    private function hasCapability(
        ?User      $user,
        Capability $capability
    ): bool
    {
        if (is_null($user)) {
            return false;
        }

        return $this
            ->membershipPlanService
            ->getSubscribedMembershipPlanForCurrentlyActiveOrganization($user)
            ->hasCapability($capability);
    }
}
