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
use DateTimeImmutable;
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

    /** @return PackageName[] */
    public function getBuyableCreditPackageNames(): array
    {
        return [
            PackageName::LingoSyncCreditsFor5Minutes,
            PackageName::LingoSyncCreditsFor10Minutes,
            PackageName::LingoSyncCreditsFor30Minutes,
            PackageName::LingoSyncCreditsFor60Minutes,
        ];
    }

    /**
     * @throws \Exception
     */
    public function depleteCreditsFromLingoSyncProcess(
        LingoSyncProcess   $causingLingoSyncProcess,
        ?DateTimeImmutable $createdAt = null
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
            $causingLingoSyncProcess,
            null,
            $createdAt
        );

        $this->entityManager->persist($lingoSyncCreditPosition);
        $this->entityManager->flush();
    }

    /**
     * @throws \Exception
     */
    public function topUpCreditsFromMembershipPlanSubscription(
        Subscription       $causingSubscription,
        ?DateTimeImmutable $createdAt = null
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
            $causingSubscription,
            null,
            null,
            null,
            $createdAt
        );

        $this->entityManager->persist($lingoSyncCreditPosition);
        $this->entityManager->flush();
    }

    /**
     * @throws \Exception
     */
    public function topUpCreditsFromPackagePurchase(
        Purchase           $causingPurchase,
        ?DateTimeImmutable $createdAt = null
    ): void
    {
        $creditsAmount = match ($causingPurchase->getPackageName()) {
            PackageName::LingoSyncCreditsFor5Minutes  =>  5 * 60,
            PackageName::LingoSyncCreditsFor10Minutes => 10 * 60,
            PackageName::LingoSyncCreditsFor30Minutes => 30 * 60,
            PackageName::LingoSyncCreditsFor60Minutes => 60 * 60,

            default => null,
        };

        if (is_null($creditsAmount)) {
            return;
        }

        $lingoSyncCreditPosition = new LingoSyncCreditPosition(
            $creditsAmount,
            null,
            $causingPurchase,
            null,
            null,
            $createdAt
        );

        $this->entityManager->persist($lingoSyncCreditPosition);
        $this->entityManager->flush();
    }

    /**
     * @throws \Exception
     */
    public function topUpCreditsFromUserVerification(
        User               $causingUser,
        ?DateTimeImmutable $createdAt = null
    ): void
    {
        $lingoSyncCreditPosition = new LingoSyncCreditPosition(
            60 * 5,
            null,
            null,
            null,
            $causingUser,
            $createdAt
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

    /**
     * @throws Exception
     * @return LingoSyncCreditPosition[]
     */
    public function getPositionsForOrganization(
        Organization $organization
    ): array
    {
        $sql = "
            SELECT p.id
            
            FROM {$this->entityManager->getClassMetadata(LingoSyncCreditPosition::class)->getTableName()} p
            
            WHERE p.owning_users_id = :userId
            
            ORDER BY p.created_at DESC
            
            ;
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->bindValue(':userId', $organization->getOwningUser()->getId());
        $resultSet = $stmt->executeQuery();

        $positions = [];
        foreach ($resultSet->fetchAllAssociative() as $row) {
            $positions[] = $this->entityManager->find(LingoSyncCreditPosition::class, $row['id']);
        }

        return $positions;
    }
}
