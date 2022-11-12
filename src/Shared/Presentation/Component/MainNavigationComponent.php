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

        if (    is_null($user)
            || !$user->isRegistered()
        ) {
            $this->entries = [
                new NavigationEntry(
                    'mainnav.homepage',
                    'shared.presentation.contentpages.homepage'
                ),

                new NavigationEntry(
                    'mainnav.features',
                    'shared.presentation.contentpages.features'
                ),

                new NavigationEntry(
                    'mainnav.pricing',
                    'shared.presentation.contentpages.pricing'
                ),
            ];
        } else {
            $this->entries = [
                new NavigationEntry(
                    'mainnav.dashboard',
                    'videobasedmarketing.dashboard.presentation.show_registered'
                ),

                new NavigationEntry(
                    'mainnav.membership',
                    'videobasedmarketing.membership.presentation.overview',
                    []
                ),

                new NavigationEntry(
                    'mainnav.recordings',
                    'videobasedmarketing.recordings.presentation.videos.overview',
                    ['videobasedmarketing.recordings.presentation.recording_studio']
                ),

                /*
                new NavigationEntry(
                    'mainnav.presentationpages',
                    'videobasedmarketing.presentationpages.presentation.overview',
                    ['videobasedmarketing.presentationpages.presentation.draft.editor']
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
