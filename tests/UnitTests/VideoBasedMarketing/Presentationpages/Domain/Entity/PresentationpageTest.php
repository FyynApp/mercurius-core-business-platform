<?php

namespace App\Tests\UnitTests\VideoBasedMarketing\Presentationpages\Domain\Entity;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Presentationpages\Domain\Entity\Presentationpage;
use PHPUnit\Framework\TestCase;


class PresentationpageTest
    extends TestCase
{
    public function test(): void
    {
        $u = new User();
        $p = new Presentationpage($u);
        $p->setTitle('Hello, World.');

        $this->assertSame('Hello, World.', $p->getTitle());
    }
}
