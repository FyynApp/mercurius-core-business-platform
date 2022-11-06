<?php

namespace App\Tests\Unit\Entity\Feature\Presentationpages;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use PHPUnit\Framework\TestCase;


class PresentationpageTest
    extends TestCase
{
    public function test()
    {
        $u = new User();
        $t = new \App\VideoBasedMarketing\Presentationpages\Domain\Entity\Presentationpage($u);
        $t->setTitle('Hello, World.');

        $this->assertSame('Hello, World.', $t->getTitle());
    }
}
