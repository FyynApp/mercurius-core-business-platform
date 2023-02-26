<?php

namespace App\VideoBasedMarketing\Organization\Domain\Enum;

enum AccessRight: string
{
    case FULL_ACCESS = 'full_access';
    case INVITE_ORGANIZATION_MEMBERS = 'invite_organization_members';
    case EDIT_CUSTOM_LOGO_SETTINGS   = 'edit_custom_logo_settings';
    case EDIT_CUSTOM_DOMAIN_SETTINGS = 'edit_custom_domain_settings';
}
