<?php

namespace App\VideoBasedMarketing\LingoSync\Presentation\Component;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncProcess;
use App\VideoBasedMarketing\LingoSync\Domain\Service\LingoSyncDomainService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    'videobasedmarketing_lingo_sync_status_widget',
    '@videobasedmarketing.lingo_sync/status_widget_live_component.html.twig'
)]
class StatusWidgetLiveComponent
    extends AbstractController
{
    use DefaultActionTrait;

    private LingoSyncDomainService $lingoSyncDomainService;

    public function __construct(
        LingoSyncDomainService $lingoSyncDomainService
    ) {
        $this->lingoSyncDomainService = $lingoSyncDomainService;
    }

    public function shouldPoll(): bool
    {
        foreach ($this->getProcesses() as $process) {
            if (!$process->isFinished()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return LingoSyncProcess[]
     * @throws Exception
     */
    public function getProcesses(): array
    {
        /** @var User $user */
        $user = $this->getUser();

        if (is_null($user)) {
            return [];
        }

        return $this
            ->lingoSyncDomainService
            ->getProcessesNotOlderThan(
                $user,
                DateAndTimeService::getDateTime('-1 hour')
            );
    }
}
