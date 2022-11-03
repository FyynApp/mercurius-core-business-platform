<?php

namespace App\Tests\Unit\Entity\Feature\Presentationpages;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Presentationpages\Presentationpage;
use PHPUnit\Framework\TestCase;


class PresentationpageTest
    extends TestCase
{
    public function test()
    {
        $u = new User();
        $t = new Presentationpage($u);
        $t->setTitle('Hello, World.');

        $this->assertSame('Hello, World.', $t->getTitle());
    }
}
