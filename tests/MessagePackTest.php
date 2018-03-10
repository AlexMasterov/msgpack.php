<?php
declare(strict_types=1);

namespace MessagePack\Tests;

use MessagePack\MessagePack;
use PHPUnit\Framework\TestCase;

final class MessagePackTest extends TestCase
{
    /** @test */
    public function it_works_correctly(): void
    {
        $data = "\xc3";
        $type = true;

        self::assertSame($data, MessagePack::encode($type));
        self::assertSame($type, MessagePack::decode($data));
    }
}
