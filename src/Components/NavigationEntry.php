<?php

namespace App\Components;

class NavigationEntry
{
    private string $displayNameTransId;

    private string $routeName;

    private bool $isActive;

    public function __construct(string $displayNameTransId, string $routeName)
    {
        $this->displayNameTransId = $displayNameTransId;
        $this->routeName = $routeName;
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


    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }
}
