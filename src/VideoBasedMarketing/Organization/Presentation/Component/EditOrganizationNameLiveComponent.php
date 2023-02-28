<?php

namespace App\VideoBasedMarketing\Organization\Presentation\Component;

use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    'videobasedmarketing_organization_edit_organization_name',
    '@videobasedmarketing.organization/organization/edit_organization_name_live_component.html.twig'
)]
class EditOrganizationNameLiveComponent
    extends AbstractController
{
    use DefaultActionTrait;

    public function __construct(
        readonly private EntityManagerInterface $entityManager
    ) {

    }

    #[LiveProp]
    public ?Organization $organization = null;

    #[LiveProp]
    public bool $isBeingEdited = false;


    public function mount(?Organization $organization = null)
    {
        $this->organization = $organization;
    }

    #[LiveAction]
    public function startEditing(): void
    {
        $this->isBeingEdited = true;
    }
}
