<?php

namespace App\Shared\Infrastructure\Service;

use InvalidArgumentException;


class ValueFormatsService
{
    public static function isValidGuid($guid): bool
    {
        if (!is_string($guid)) {
            return false;
        }

        return preg_match('/^[a-z0-9]{8}-([a-z0-9]{4}-){3}[a-z0-9]{12}$/', $guid) === 1;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function validGuidOrThrow(
        $guid,
        bool $canBeNull = false
    ): void
    {
        if ($canBeNull === true && is_null($guid)) {
            return;
        }

        if (!self::isValidGuid($guid)) {
            throw new InvalidArgumentException("Value {$guid} is not a valid GUID.");
        }
    }
}
