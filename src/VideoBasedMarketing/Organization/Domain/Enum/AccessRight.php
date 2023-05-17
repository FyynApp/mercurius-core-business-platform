<?php

namespace App\VideoBasedMarketing\Organization\Domain\Enum;

enum AccessRight: string
{
    case FULL_ACCESS = 'full_access';
    case EDIT_ORGANIZATION_NAME = 'edit_organization_name';
    case INVITE_ORGANIZATION_MEMBERS = 'invite_organization_members';
    case SEE_ORGANIZATION_GROUPS_AND_MEMBERS = 'see_organization_groups_and_members';
    case MOVE_ORGANIZATION_MEMBERS_INTO_GROUPS = 'move_organization_members_into_groups';
    case EDIT_CUSTOM_LOGO_SETTINGS   = 'edit_custom_logo_settings';
    case EDIT_CUSTOM_DOMAIN_SETTINGS = 'edit_custom_domain_settings';
    case EDIT_FOLDER_VISIBILITY_FOR_NON_ADMINISTRATORS = 'edit_folder_visibility_for_non_administrators';
    case DEFINE_DEFAULT_FOLDER_FOR_ADMINISTRATOR_RECORDINGS = 'define_default_folder_for_administrator_recordings';
    case CAN_STORE_NEW_RECORDINGS_IN_DEFAULT_FOLDER_FOR_ADMINISTRATOR_RECORDINGS = 'can_store_new_recordings_in_default_folder_for_administrator_recordings';
}
