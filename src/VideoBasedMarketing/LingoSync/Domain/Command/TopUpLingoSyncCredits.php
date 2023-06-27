<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\Command;

use App\Shared\Infrastructure\Enum\DateTimeFormat;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncProcess;
use App\VideoBasedMarketing\LingoSync\Domain\Service\LingoSyncCreditsDomainService;
use App\VideoBasedMarketing\Membership\Domain\Entity\Subscription;
use App\VideoBasedMarketing\Membership\Domain\Enum\SubscriptionStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:videobasedmarketing:lingosync:top-up-credits',
    description: '',
    aliases: ['top-up-credits']
)]
class TopUpLingoSyncCredits
    extends Command
{
    private readonly LingoSyncCreditsDomainService $lingoSyncCreditsDomainService;
    private readonly EntityManagerInterface $entityManager;

    public function __construct(
        LingoSyncCreditsDomainService $lingoSyncCreditsDomainService,
        EntityManagerInterface        $entityManager
    )
    {
        $this->lingoSyncCreditsDomainService = $lingoSyncCreditsDomainService;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    public function configure(): void
    {
        parent::configure();
    }

    /**
     * @throws Exception
     */
    protected function execute(
        InputInterface  $input,
        OutputInterface $output
    ): int
    {
        $sql = "
            SELECT id
        
            FROM {$this->entityManager->getClassMetadata(User::class)->getTableName()} u
            
            WHERE u.is_verified = 1
        
            ;
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $resultSet = $stmt->executeQuery();

        foreach ($resultSet->fetchAllAssociative() as $row) {
            $user = $this->entityManager->find(
                User::class,
                $row['id']
            );

            if (is_null($user->getCreatedAt())) {
                $dateTime = null;
            } else {
                $dateTime = new DateTimeImmutable($user->getCreatedAt()->format(DateTimeFormat::Iso8601->value));
            }

            $this
                ->lingoSyncCreditsDomainService
                ->topUpCreditsFromUserVerification(
                    $user,
                    $dateTime
                );
        }


        $sql = "
            SELECT id
        
            FROM {$this->entityManager->getClassMetadata(Subscription::class)->getTableName()} s
            
            WHERE s.status = '" . SubscriptionStatus::Active->value . "'
        
            ;
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $resultSet = $stmt->executeQuery();

        foreach ($resultSet->fetchAllAssociative() as $row) {
            $subscription = $this->entityManager->find(
                Subscription::class,
                $row['id']
            );

            $this
                ->lingoSyncCreditsDomainService
                ->topUpCreditsFromMembershipPlanSubscription(
                    $subscription,
                    new DateTimeImmutable($subscription->getCreatedAt()->format(DateTimeFormat::Iso8601->value))
                );
        }


        $sql = "
            SELECT id
        
            FROM {$this->entityManager->getClassMetadata(LingoSyncProcess::class)->getTableName()} p
           
            ;
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $resultSet = $stmt->executeQuery();

        foreach ($resultSet->fetchAllAssociative() as $row) {
            $lingoSyncProcess = $this->entityManager->find(
                LingoSyncProcess::class,
                $row['id']
            );

            if (    $lingoSyncProcess->isFinished()
                && !$lingoSyncProcess->hasErrored()
                && !$lingoSyncProcess->wasStopped()
            ) {
                $this
                    ->lingoSyncCreditsDomainService
                    ->depleteCreditsFromLingoSyncProcess(
                        $lingoSyncProcess,
                        new DateTimeImmutable($lingoSyncProcess->getCreatedAt()->format(DateTimeFormat::Iso8601->value))
                    );
            }
        }


        return 0;
    }
}
