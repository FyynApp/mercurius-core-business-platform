<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Command\Devhelper;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Infrastructure\Enum\ActiveCampaignContactTag;
use App\VideoBasedMarketing\Account\Infrastructure\Service\ActiveCampaignService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:videobasedmarketing:infrastructure:devhelper:create-contact-at-activecampaign',
    description: 'Create a contact at active campaign',
    aliases: ['create-contact-at-activecampaign']
)]
class CreateContactAtActiveCampaign
    extends Command
{
    private readonly ActiveCampaignService $activeCampaignService;

    private readonly EntityManagerInterface $entityManager;

    public function __construct(
        ActiveCampaignService  $activeCampaignService,
        EntityManagerInterface $entityManager
    )
    {
        $this->activeCampaignService = $activeCampaignService;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(
        InputInterface  $input,
        OutputInterface $output
    ): int
    {
        $contact = $this->activeCampaignService->createContactIfNotExists(
            $this->entityManager->find(
                User::class,
                '1ed7e43d-8990-6abe-a482-1ded198af678'
            )
        );

        $this->activeCampaignService->addTagToContact(
            $contact,
            ActiveCampaignContactTag::RegisteredThroughTheChromeExtension
        );

        return 0;
    }
}
