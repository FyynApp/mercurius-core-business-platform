<?php

namespace App\Components\Feature\Presentationpages;

use App\Entity\Feature\Presentationpages\Presentationpage;
use App\Form\Type\Feature\Presentationpages\PresentationpageType;
use App\Service\Feature\Recordings\VideoService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('feature_presentationpages_edit_form', 'feature/presentationpages/edit_form_live_component.html.twig')]
class PresentationspageEditFormLiveComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp(fieldName: 'data')]
    public ?Presentationpage $presentationpage = null;

    #[LiveProp]
    public string $posterStillAssetUrl = '';

    #[LiveProp]
    public string $posterAnimatedAssetUrl = '';

    private VideoService $videoService;

    private LoggerInterface $logger;

    private EntityManagerInterface $entityManager;


    public function __construct(
        VideoService $videoService,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager
    )
    {
        $this->videoService = $videoService;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    public function mount(
        ?Presentationpage $presentationpage = null,
    ) {
        $this->presentationpage = $presentationpage;
        if (!is_null($presentationpage)) {
            $this->posterStillAssetUrl = $this->videoService->getPosterStillAssetUrl($presentationpage->getVideo());
            $this->posterAnimatedAssetUrl = $this->videoService->getPosterAnimatedAssetUrl($presentationpage->getVideo());
        }
    }

    protected function instantiateForm(): FormInterface
    {
        $this->logger->info('instantiateForm has been called.');

        $this->logger->info("presentationpage.title is {$this->presentationpage->getTitle()}");
        $this->logger->info("presentationpage.welcomeText is {$this->presentationpage->getWelcomeText()}");

        return $this->createForm(
            PresentationpageType::class,
            $this->presentationpage
        );
    }
}
