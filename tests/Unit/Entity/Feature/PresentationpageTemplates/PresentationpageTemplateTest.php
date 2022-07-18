<?php

namespace App\Tests\Unit\Entity\Feature\PresentationpageTemplates;

use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplate;
use PHPUnit\Framework\TestCase;

class PresentationpageTemplateTest extends TestCase
{
    public function test()
    {
        $t = new PresentationpageTemplate();
        $t->setTitle('Hello, World.');

        $this->assertSame('Hello, World.', $t->getTitle());
    }
}
