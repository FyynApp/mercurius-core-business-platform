<?php

namespace App\Shared\Infrastructure\Controller;

use App\Shared\Infrastructure\Entity\VerifyAndGetOrganizationAndEntityResult;
use App\Shared\Infrastructure\Entity\VerifyAndGetUserAndEntityResult;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\Account\Infrastructure\Enum\RequestParameter;
use App\VideoBasedMarketing\Organization\Domain\Entity\OrganizationOwnedEntityInterface;
use App\VideoBasedMarketing\Organization\Domain\Service\OrganizationDomainService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;
use Symfony\Component\HttpFoundation\Request;


abstract class AbstractController
    extends SymfonyAbstractController
{
    public function __construct(
        private readonly EntityManagerInterface    $entityManager,
        private readonly OrganizationDomainService $organizationDomainService
    )
    {
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
        AccessAttribute $votingAttribute
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

    public function verifyAndGetOrganizationAndEntity(
        string          $entityClassName,
        string          $entityId,
        AccessAttribute $votingAttribute
    ): VerifyAndGetOrganizationAndEntityResult
    {
        $user = $this->getUser();

        if (is_null($user)) {
            throw $this->createAccessDeniedException('No user.');
        }

        /** @var OrganizationOwnedEntityInterface|null $entity */
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

        return new VerifyAndGetOrganizationAndEntityResult(
            $this->organizationDomainService->getCurrentlyActiveOrganizationOfUser($user),
            $entity
        );
    }

    protected function valueifyBoolParameter(
        RequestParameter $requestParameter,
        Request          $request
    ): bool
    {
        return $request->get($requestParameter->value) === 'true';
    }

    protected function urlifyBoolValue(
        bool $value
    ): string
    {
        return ($value ? 'yes' : 'no');
    }
}
