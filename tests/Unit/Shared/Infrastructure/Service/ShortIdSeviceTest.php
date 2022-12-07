<?php

namespace App\Tests\Unit\Shared\Infrastructure\Service;

use App\Shared\Infrastructure\Service\ShortIdService;
use PHPUnit\Framework\TestCase;


class ShortIdSeviceTest
    extends TestCase
{
    public function test(): void
    {
        $this->assertSame(
            '1',
            ShortIdService::encode(1)
        );

        $this->assertSame(
            'Z',
            ShortIdService::encode(50)
        );

        $this->assertSame(
            '10',
            ShortIdService::encode(51)
        );

        $this->assertSame(
            '25QfVpY',
            ShortIdService::encode(37203685477)
        );

        $this->assertSame(
            '1wtsJwCMfnRv',
            ShortIdService::encode(PHP_INT_MAX)
        );
    }
}
