<?php

namespace App\Tests\Unit\Service\Aspect\ShortId;

use App\Service\Aspect\ShortId\ShortIdService;
use PHPUnit\Framework\TestCase;


class ShortIdSeviceTest extends TestCase
{
    public function test()
    {
        $this->assertSame(
            '3',
            ShortIdService::encode(1)
        );

        $this->assertSame(
            '3ywvLyFPhqTx',
            ShortIdService::encode(PHP_INT_MAX)
        );
    }
}
