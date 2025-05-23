<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\SymfonyMessageHandler;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Infrastructure\SymfonyMessage\SyncUserToActiveCampaignCommandSymfonyMessage;
use App\VideoBasedMarketing\Account\Infrastructure\Service\ActiveCampaignService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsMessageHandler]
class SyncUserToActiveCampaignCommandSymfonyMessageHandler
{
    private ActiveCampaignService $activeCampaignService;

    private EntityManagerInterface $entityManager;


    public function __construct(
        ActiveCampaignService  $activeCampaignService,
        EntityManagerInterface $entityManager
    )
    {
        $this->activeCampaignService = $activeCampaignService;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(
        SyncUserToActiveCampaignCommandSymfonyMessage $message
    ): void
    {
        /** @var null|User $user */
        $user = $this->entityManager->find(
            User::class,
            $message->getUserId()
        );

        if (is_null($user)) {
            throw new UnrecoverableMessageHandlingException(
                "Could not find user with id '{$message->getUserId()}'."
            );
        }

        if (is_null($user->getActiveCampaignContact())) {
            $this->activeCampaignService->createContactIfNotExists($user);
        }

        foreach ($message->getContactTags() as $contactTag) {
            $this->activeCampaignService->addTagToContact(
                $user->getActiveCampaignContact(),
                $contactTag
            );
        }
    }
}
