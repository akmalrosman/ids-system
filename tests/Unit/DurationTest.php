<?php

namespace Tests\Unit;

use App\Support\Duration;
use PHPUnit\Framework\TestCase;

class DurationTest extends TestCase
{
    public function test_clock_format(): void
    {
        $this->assertSame('2:10:49.000', Duration::clock(7849));
        $this->assertSame('0:00:01.500', Duration::clock(1.5));
        $this->assertSame('—', Duration::clock(null));
    }

    public function test_human_format(): void
    {
        $this->assertSame('2 hour 10 min 49 sec', Duration::human(7849));
        $this->assertSame('12 hr 55 min 36 sec', Duration::human(46536, short: true));
        $this->assertSame('45 sec', Duration::human(45));
    }
}
