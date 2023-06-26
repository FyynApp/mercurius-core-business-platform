<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncCreditPosition;
use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncProcess;
use App\VideoBasedMarketing\Membership\Domain\Entity\Purchase;
use App\VideoBasedMarketing\Membership\Domain\Entity\Subscription;
use App\VideoBasedMarketing\Membership\Domain\Enum\PackageName;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipPlanService;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;


readonly class LingoSyncCreditsDomainService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MembershipPlanService $membershipPlanService
    )
    {
    }

    /**
     * @throws \Exception
     */
    public function topUpCreditsFromMembershipPlanSubscription(
        Subscription $subscription
    ): void
    {
        $membershipPlan = $this
            ->membershipPlanService
            ->getMembershipPlanByName(
                $subscription->getMembershipPlanName()
            );

        $creditsAmount = $membershipPlan->getAmountOfLingoSyncCreditsPerMonth();

        $lingoSyncCreditPosition = new LingoSyncCreditPosition(
            $creditsAmount,
            $subscription
        );

        $this->entityManager->persist($lingoSyncCreditPosition);
        $this->entityManager->flush();
    }

    /**
     * @throws \Exception
     */
    public function topUpCreditsFromPackagePurchase(
        Purchase $purchase
    ): void
    {
        $creditsAmount = match ($purchase->getPackageName()) {
            PackageName::FreeLingoSyncCreditsFor10Minutes, PackageName::LingoSyncCreditsFor10Minutes => 10,
            PackageName::LingoSyncCreditsFor5Minutes => 5,

            default => null,
        };

        if (is_null($creditsAmount)) {
            return;
        }

        $lingoSyncCreditPosition = new LingoSyncCreditPosition(
            $creditsAmount,
            null,
            $purchase
        );

        $this->entityManager->persist($lingoSyncCreditPosition);
        $this->entityManager->flush();
    }

    /**
     * @throws Exception
     */
    public function getTotalAmountOfCreditsForUser(
        User $user
    ): int
    {
        return $this->getTotalAmountOfCreditsForOrganization(
            $user->getCurrentlyActiveOrganization()
        );
    }

    /**
     * @throws Exception
     */
    public function getTotalAmountOfCreditsForOrganization(
        Organization $organization
    ): int
    {
        $amount = 0;

        $sql = "
            SELECT SUM(c.amount) AS amount
            
            FROM {$this->entityManager->getClassMetadata(LingoSyncCreditPosition::class)->getTableName()} c
            
            INNER JOIN {$this->entityManager->getClassMetadata(Subscription::class)->getTableName()} s
            ON s.id = c.subscriptions_id
            
            INNER JOIN {$this->entityManager->getClassMetadata(User::class)->getTableName()} u
            
            ON s.users_id = u.id
            
            WHERE u.id = :userId
            
            ;
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->bindValue(':userId', $organization->getOwningUser()->getId());
        $resultSet = $stmt->executeQuery();

        foreach ($resultSet->fetchAllAssociative() as $row) {
            $amount += (int)$row['amount'];
        }


        $sql = "
            SELECT SUM(c.amount) AS amount
            
            FROM {$this->entityManager->getClassMetadata(LingoSyncCreditPosition::class)->getTableName()} c
            
            INNER JOIN {$this->entityManager->getClassMetadata(Purchase::class)->getTableName()} p
            ON p.id = c.purchases_id
            
            INNER JOIN {$this->entityManager->getClassMetadata(User::class)->getTableName()} u
            
            ON s.users_id = u.id
            
            WHERE u.id = :userId
            
            ;
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->bindValue(':userId', $organization->getOwningUser()->getId());
        $resultSet = $stmt->executeQuery();

        foreach ($resultSet->fetchAllAssociative() as $row) {
            $amount += (int)$row['amount'];
        }

        return $amount;
    }

    /**
     * @throws Exception
     */
    public function getAmountOfUsedCreditsForOrganization(
        Organization $organization
    ): int
    {
        $sql = "
            SELECT p.id AS id
            
            FROM {$this->entityManager->getClassMetadata(LingoSyncProcess::class)->getTableName()} p
            
            INNER JOIN {$this->entityManager->getClassMetadata(Video::class)->getTableName()} v
            ON p.videos_id = v.id
                    
            INNER JOIN {$this->entityManager->getClassMetadata(Organization::class)->getTableName()} o
            ON v.organizations_id = o.id            
            
            WHERE o.owning_users_id = :userId

            ;
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->bindValue(':userId', $organization->getOwningUser()->getId());
        $resultSet = $stmt->executeQuery();

        $seconds = 0.0;
        foreach ($resultSet->fetchAllAssociative() as $row) {
            /** @var LingoSyncProcess $process */
            $process = $this->entityManager->find(LingoSyncProcess::class, $row['id']);

            if ($process->hasErrored()) {
                continue;
            }

            $seconds += (float)$process->getVideo()->getSeconds();
        }

        return (int)floor($seconds);
    }

    /**
     * @throws Exception
     */
    public function getAmountOfAvailableCreditsForOrganization(
        Organization $organization
    ): int
    {
        return $this->getTotalAmountOfCreditsForOrganization($organization)
            - $this->getAmountOfUsedCreditsForOrganization($organization);
    }

    /**
     * @throws Exception
     */
    public function organizationHasEnoughAvailableCreditsForVideo(
        Video $video
    ): bool
    {
        return $this->getAmountOfAvailableCreditsForOrganization(
                $video->getOrganization()
            ) >= (int)floor($video->getSeconds());
    }
}
