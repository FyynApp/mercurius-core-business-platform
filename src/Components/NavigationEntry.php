<?php

namespace App\Components;

class NavigationEntry
{
    private string $displayNameTransId;

    private string $routeName;

    /** @var string[] */
    private array $additionalRouteNames;

    private bool $isActive;

    /**
     * @param string[] $additionalRouteNames
     */
    public function __construct(
        string $displayNameTransId,
        string $routeName,
        array  $additionalRouteNames = []
    )
    {
        $this->displayNameTransId = $displayNameTransId;
        $this->routeName = $routeName;
        $this->additionalRouteNames = $additionalRouteNames;
        $this->isActive = false;
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


    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }
}
