<?php

namespace App\Shared\Presentation\Entity;

class NavigationEntry
{
    private string $displayNameTransId;

    private string $routeName;

    /** @var string[] */
    private array $additionalRouteNames;

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
}
