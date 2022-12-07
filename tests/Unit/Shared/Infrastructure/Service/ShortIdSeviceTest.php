<?php

namespace App\Tests\Unit\Shared\Infrastructure\Service;

use App\Shared\Infrastructure\Service\ShortIdService;
use PHPUnit\Framework\TestCase;


class ShortIdSeviceTest
    extends TestCase
{
    public function test()
    {
        $this->assertSame(
            '3',
            ShortIdService::encode(1)
        );

        $this->assertSame(
            '47ShXr-',
            ShortIdService::encode(37203685477)
        );

        $this->assertSame(
            '3ywvLyFPhqTx',
            ShortIdService::encode(PHP_INT_MAX)
        );
    }
}
