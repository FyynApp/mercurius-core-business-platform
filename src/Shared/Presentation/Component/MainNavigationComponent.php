<?php

namespace App\Shared\Presentation\Component;

use App\Shared\Presentation\Entity\NavigationEntry;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;


#[AsTwigComponent(
    'main_navigation',
    '@shared/navigation/main_navigation.component.html.twig'
)]
class MainNavigationComponent
{
    #[ExposeInTemplate]
    private string $type = 'wide';

    /** @var NavigationEntry[] */
    #[ExposeInTemplate]
    private array $entries = [];

    public function mount(
        ?User   $user,
        Request $request,
        string  $type = 'wide'
    ): void
    {
        if (!in_array($type, ['wide', 'stacked'])) {
            throw new InvalidArgumentException("type must be 'wide' or 'stacked', got '$type'.");
        }

        $this->type = $type;

        if (is_null($user)) {
            $this->entries = [
                new NavigationEntry(
                    'mainnav.homepage',
                    'shared.contentpages.homepage'
                ),

                new NavigationEntry(
                    'mainnav.features',
                    'shared.contentpages.features'
                ),

                new NavigationEntry(
                    'mainnav.pricing',
                    'shared.contentpages.pricing'
                ),
            ];
        } else {
            $this->entries = [
                new NavigationEntry(
                    'mainnav.dashboard',
                    'videobasedmarketing.dashboard.show'
                ),

                new NavigationEntry(
                    'mainnav.membership',
                    'videobasedmarketing.membership.overview',
                    []
                ),

                new NavigationEntry(
                    'mainnav.recordings',
                    'videobasedmarketing.recordings.videos.overview',
                    ['videobasedmarketing.recordings.recording_studio']
                ),

                /*
                new NavigationEntry(
                    'mainnav.presentationpages',
                    'videobasedmarketing.presentationpages.overview',
                    ['videobasedmarketing.presentationpages.draft.editor']
                ),
                */
            ];
        }

        foreach ($this->entries as $entry) {
            if ($request->attributes->get('_route') === $entry->getRouteName()
                || in_array($request->attributes->get('_route'), $entry->getAdditionalRouteNames())
            ) {
                $entry->setIsActive(true);
                break;
            }
        }
    }

    /** @return NavigationEntry[] */
    public function getEntries(): array
    {
        return $this->entries;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
