<?php

namespace App\Shared\Presentation\Entity;

class NavigationEntry
{
    private string $displayNameTransId;

    private string $routeName;

    /** @var string[] */
    private array $additionalRouteNames;

    private string $iconSvg;

    /**
     * @param string[] $additionalRouteNames
     */
    public function __construct(
        string $displayNameTransId,
        string $routeName,
        array  $additionalRouteNames,
        string $iconSvg
    )
    {
        $this->displayNameTransId = $displayNameTransId;
        $this->routeName = $routeName;
        $this->additionalRouteNames = $additionalRouteNames;
        $this->iconSvg = $iconSvg;
    }

    public function getDisplayNameTransId(): string
    {
        return $this->displayNameTransId;
    }


    public function getRouteName(): string
    {
        return $this->routeName;
    }


    /**
     * @return string[]
     */
    public function getAdditionalRouteNames(): array
    {
        return $this->additionalRouteNames;
    }


    public function getIconSvg(): string
    {
        return $this->iconSvg;
    }
}
