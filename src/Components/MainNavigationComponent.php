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
                new NavigationEntry('mainnav.homepage', 'feature.landingpages.homepage'),
                new NavigationEntry('mainnav.features', 'feature.landingpages.features'),
                new NavigationEntry('mainnav.pricing', 'feature.landingpages.pricing'),
            ];
        } else {
            $this->entries = [
                new NavigationEntry('mainnav.dashboard', 'feature.landingpages.homepage'),
            ];
        }

        foreach ($this->entries as $entry) {
            if ($request->attributes->get('_route') === $entry->getRouteName()) {
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
