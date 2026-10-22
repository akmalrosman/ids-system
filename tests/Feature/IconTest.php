<?php

namespace Tests\Feature;

use App\View\Components\Icon;
use Tests\TestCase;

class IconTest extends TestCase
{
    public function test_outer_size_is_removed_but_inner_shapes_keep_theirs(): void
    {
        // pause / layout-grid are drawn with <rect width=… height=…>; losing those made them invisible.
        $svg = (new Icon('pause'))->svg;

        $this->assertStringStartsWith('<svg xmlns=', $svg);
        $this->assertStringContainsString('<rect x="14" y="3" width="5" height="18"', $svg);
    }
}
