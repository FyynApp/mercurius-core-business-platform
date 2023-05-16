<?php

namespace App\VideoBasedMarketing\Account\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Membership\Domain\Enum\Capability;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipService;
use App\VideoBasedMarketing\Organization\Domain\Enum\AccessRight;
use App\VideoBasedMarketing\Organization\Domain\Service\OrganizationDomainService;


readonly class CapabilitiesService
{
    public function __construct(
        private MembershipService         $membershipService,
        private OrganizationDomainService $organizationDomainService
    )
    {}

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


    private function hasCapability(
        ?User      $user,
        Capability $capability
    ): bool
    {
        if (is_null($user)) {
            return false;
        }

        return $this
            ->membershipService
            ->getSubscribedMembershipPlanForCurrentlyActiveOrganization($user)
            ->hasCapability($capability);
    }
}
