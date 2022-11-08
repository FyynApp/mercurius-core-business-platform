<?php

namespace App\Shared\Presentation\Component;

use App\Shared\Presentation\Entity\NavigationEntry;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;


#[AsTwigComponent(
    'side_navigation',
    '@shared/navigation/side_navigation.component.html.twig'
)]
class SideNavigationComponent
{
    #[ExposeInTemplate]
    private string $type = 'wide';

    /** @var NavigationEntry[] */
    #[ExposeInTemplate]
    private array $entries = [];

    #[ExposeInTemplate]
    private ?User $user = null;

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
        $this->user = $user;

        if (is_null($this->user)) {
            $this->entries = [
                new NavigationEntry('sidenav.sign_in', 'videobasedmarketing.account.presentation.sign_in'),
                new NavigationEntry('sidenav.sign_up', 'videobasedmarketing.account.presentation.sign_up'),
            ];
        } else {
            $this->entries = [
                new NavigationEntry('sidenav.sign_out', 'videobasedmarketing.account.infrastructure.sign_out'),
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

    public function getUser(): ?User
    {
        return $this->user;
    }
}
