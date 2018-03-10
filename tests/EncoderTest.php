<?php
declare(strict_types=1);

namespace MessagePack\Tests;

use MessagePack\Tests\Data\Type;
use MessagePack\{
    Encoder,
    Exception\EncodingFailed
};
use PHPUnit\Framework\TestCase;

final class EncoderTest extends TestCase
{
    /**
     * @test
     * @dataProvider supportedTypes
     */
    public function it_encodes_type_correctly($expected, $type): void
    {
        self::assertSame($expected, $this->encode($type));
    }

    public function supportedTypes(): Iterable
    {
        yield from Type::nil();
        yield from Type::bool();
        yield from Type::int();
        yield from Type::uint();
        yield from Type::uint64();
        yield from Type::double();
        yield from Type::string();
        yield from Type::array();
        yield from Type::map();
    }

    /**
     * @test
     * @dataProvider unsupportedTypes
     */
    public function it_throws_when_encode_unsupported_type($type): void
    {
        try {
            $this->encode($type);
        } catch (EncodingFailed $e) {
            self::assertSame($type, $e->getValue());
            return;
        }

        self::fail('EncodingFailed was not thrown.');
    }

    public function unsupportedTypes(): Iterable
    {
        yield [(object) []];
        yield [new \stdClass()];
        yield [fopen('php://temp', 'r+')];
    }

    /**
     * @test
     * @dataProvider typeDetectionModes
     */
    public function it_sets_encoding_mode_correctly($mode, $type, $expected): void
    {
        self::assertSame($expected, $this->encode($type, $mode));
    }

    public function typeDetectionModes(): Iterable
    {
        yield [0    /* auto */,     [0 => 1], '9101'];
        yield [null /* auto */,     [0 => 1], '9101'];
        yield [Encoder::FORCE_AUTO, [0 => 1], '9101'];
        yield [Encoder::FORCE_ARR,  [1 => 2], '9102'];
        yield [Encoder::FORCE_MAP,  [0 => 1], '810001'];
        yield [Encoder::FORCE_MAP | Encoder::FORCE_ARR,  [0 => 1], '810001'];
    }

    private function encode($value, int $mode = null): string
    {
        $encoded = (new Encoder($mode))->encode($value);
        return \bin2hex($encoded);
    }
}
