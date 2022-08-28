<?php

namespace App\Tests\Unit\Entity\Feature\Presentationpages;

use App\Entity\Feature\Presentationpages\Presentationpage;
use PHPUnit\Framework\TestCase;

class PresentationpageTest extends TestCase
{
    public function test()
    {
        $t = new Presentationpage();
        $t->setTitle('Hello, World.');

        $this->assertSame('Hello, World.', $t->getTitle());
    }
}
