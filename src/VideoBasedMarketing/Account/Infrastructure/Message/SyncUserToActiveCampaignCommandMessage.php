<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Message;

use App\Shared\Infrastructure\Message\AsyncMessageInterface;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Infrastructure\Enum\ActiveCampaignContactTag;
use ValueError;

class SyncUserToActiveCampaignCommandMessage
    implements AsyncMessageInterface
{
    private string $userId;

    /** @var int[] */
    private array $contactTagValues;


    /** @param ActiveCampaignContactTag[] $contactTags */
    public function __construct(
        User  $user,
        array $contactTags = []
    )
    {
        if (is_null($user->getId())) {
            throw new ValueError('user needs an id.');
        }
        $this->userId = $user->getId();

        foreach ($contactTags as $contactTag) {
            $this->contactTagValues[] = $contactTag->value;
        }
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * @return ActiveCampaignContactTag[]
     */
    public function getContactTags(): array
    {
        return array_map(
            fn(int $value) => ActiveCampaignContactTag::from($value),
            $this->contactTagValues
        );
    }
}
