<?php

namespace App\Shared\Infrastructure\Controller;

use App\Shared\Infrastructure\Entity\VerifyAndGetUserAndEntityResult;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\Account\Domain\Enum\VotingAttribute;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;


abstract class AbstractController
    extends SymfonyAbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    public function getUser(bool $throwIfNoUser = false): ?User
    {
        /** @var null|User $user */
        $user = parent::getUser();

        if (is_null($user)) {

            if ($throwIfNoUser) {
                throw $this->createAccessDeniedException('No user.');
            }

            return null;
        }

        return $user;
    }

    public function verifyAndGetUserAndEntity(
        string          $entityClassName,
        string          $entityId,
        VotingAttribute $votingAttribute
    ): VerifyAndGetUserAndEntityResult
    {
        $user = $this->getUser();

        if (is_null($user)) {
            throw $this->createAccessDeniedException('No user.');
        }

        /** @var UserOwnedEntityInterface|null $entity */
        $entity = $this
            ->entityManager
            ->find($entityClassName, $entityId);

        if (is_null($entity)) {
            throw $this
                ->createNotFoundException(
                    "Could not find entity of type '$entityClassName' with id '$entityId'."
                );
        }

        $this->denyAccessUnlessGranted(
            $votingAttribute->value,
            $entity
        );

        return new VerifyAndGetUserAndEntityResult(
            $user,
            $entity
        );
    }
}
