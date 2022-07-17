<?php

namespace App\Components;

use App\Entity\Feature\Account\User;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent('main_navigation')]
class MainNavigationComponent
{
    #[ExposeInTemplate]
    private string $type = 'wide';

    /** @var NavigationEntry[] */
    #[ExposeInTemplate]
    private array $entries = [];

    public function mount(?User $user, Request $request, string $type = 'wide'): void
    {
        if (!in_array($type, ['wide', 'stacked'])) {
            throw new InvalidArgumentException("type must be 'wide' or 'stacked', got '$type'.");
        }

        $this->type = $type;

        if (is_null($user)) {
            $this->entries = [
                new NavigationEntry(
                    'mainnav.homepage',
                    'feature.landingpages.homepage'
                ),

                new NavigationEntry(
                    'mainnav.features',
                    'feature.landingpages.features'
                ),

                new NavigationEntry(
                    'mainnav.pricing',
                    'feature.landingpages.pricing'
                ),
            ];
        } else {
            $this->entries = [
                new NavigationEntry(
                    'mainnav.dashboard',
                    'feature.dashboard.show'
                ),

                new NavigationEntry(
                    'mainnav.presentationpage_templates',
                    'feature.presentationpage_templates.overview',
                    ['feature.presentationpage_templates.add_form']
                ),
            ];
        }

        foreach ($this->entries as $entry) {
            if (   $request->attributes->get('_route') === $entry->getRouteName()
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
