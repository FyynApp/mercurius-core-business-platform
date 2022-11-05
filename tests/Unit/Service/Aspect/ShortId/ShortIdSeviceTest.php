<?php

namespace App\Tests\Unit\Service\Aspect\ShortId;

use App\Shared\Entities\Infrastructure\Service\ShortIdService;
use PHPUnit\Framework\TestCase;


class ShortIdSeviceTest
    extends TestCase
{
    public function test()
    {
        $this->assertSame(
            '3',
            \App\Shared\Entities\Infrastructure\Service\ShortIdService::encode(1)
        );

        $this->assertSame(
            '3ywvLyFPhqTx',
            ShortIdService::encode(PHP_INT_MAX)
        );
    }
}
