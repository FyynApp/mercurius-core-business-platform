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
    public function depleteCreditsFromLingoSyncProcess(
        LingoSyncProcess $causingLingoSyncProcess
    ): void
    {
        $amount = (int)floor($causingLingoSyncProcess->getVideo()->getSeconds());
        if ($amount === 0) {
            $amount = 1;
        }

        $lingoSyncCreditPosition = new LingoSyncCreditPosition(
            $amount * -1,
            null,
            null,
            $causingLingoSyncProcess
        );

        $this->entityManager->persist($lingoSyncCreditPosition);
        $this->entityManager->flush();
    }

    /**
     * @throws \Exception
     */
    public function topUpCreditsFromMembershipPlanSubscription(
        Subscription $causingSubscription
    ): void
    {
        $membershipPlan = $this
            ->membershipPlanService
            ->getMembershipPlanByName(
                $causingSubscription->getMembershipPlanName()
            );

        $creditsAmount = $membershipPlan->getAmountOfLingoSyncCreditsPerMonth();

        $lingoSyncCreditPosition = new LingoSyncCreditPosition(
            $creditsAmount,
            $causingSubscription
        );

        $this->entityManager->persist($lingoSyncCreditPosition);
        $this->entityManager->flush();
    }

    /**
     * @throws \Exception
     */
    public function topUpCreditsFromPackagePurchase(
        Purchase $causingPurchase
    ): void
    {
        $creditsAmount = match ($causingPurchase->getPackageName()) {
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
            $causingPurchase
        );

        $this->entityManager->persist($lingoSyncCreditPosition);
        $this->entityManager->flush();
    }

    /**
     * @throws \Exception
     */
    public function topUpCreditsFromUserVerification(
        User $causingUser
    ): void
    {
        $lingoSyncCreditPosition = new LingoSyncCreditPosition(
            60 * 5,
            null,
            null,
            null,
            $causingUser
        );

        $this->entityManager->persist($lingoSyncCreditPosition);
        $this->entityManager->flush();
    }

    /**
     * @throws Exception
     */
    public function getAmountOfAvailableCreditsForOrganization(
        Organization $organization
    ): int
    {
        $amount = 0;

        $sql = "
            SELECT SUM(c.amount) AS amount
            
            FROM {$this->entityManager->getClassMetadata(LingoSyncCreditPosition::class)->getTableName()} c
            
            WHERE c.owning_users_id = :userId
            
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
    public function organizationHasEnoughAvailableCreditsForVideo(
        Video $video
    ): bool
    {
        return $this->getAmountOfAvailableCreditsForOrganization(
                $video->getOrganization()
            ) >= (int)floor($video->getSeconds());
    }
}
