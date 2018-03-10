<?php
declare(strict_types=1);

namespace MessagePack\Tests;

use PHPUnit\Framework\TestCase;
use const MessagePack\{CHR, ORD};

final class FunctionsTest extends TestCase
{
    /** @test */
    public function it_generates_correct_ascii(): void
    {
        $chr = '';
        $ord = [];

        for ($i = 0; $i <= 256; ++$i) {
            $c = \chr($i);
            $chr .= $c;
            $ord[$c] = $i;
        }
        // reset
        $ord["\X00"] = 0;

        self::assertSame($chr, CHR);
        self::assertSame($ord, ORD);
    }
}
