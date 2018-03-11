<?php
declare(strict_types=1);

namespace MessagePack\Tests;

use MessagePack\Tests\Data\Type;
use MessagePack\{Decoder, Exception\DecodingFailed};
use PHPUnit\Framework\TestCase;

final class DecoderTest extends TestCase
{
    /** @var Decoder */
    private $decoder;

    /**
     * @test
     * @dataProvider supportedData
     */
    public function it_decodes_data_correctly($data, $expected): void
    {
        self::assertSame($expected, $this->decode(\hex2bin($data)));
    }

    public function supportedData(): Iterable
    {
        yield from Type::nil();
        yield from Type::bool();
        yield from Type::string();
        yield from Type::array();
        yield from Type::map();
    }

    /**
     * @test
     * @dataProvider supportedNumberTypes
     */
    public function it_valid_decoded_number($data, $expected): void
    {
        $actual = $this->decode(\hex2bin($data));

        if (\is_nan($actual)) {
            self::assertNan($actual);
            return;
        }
        if (\is_finite($actual)) {
            self::assertFinite($actual);
            return;
        }

        self::assertSame($expected, $actual);
    }

    public function supportedNumberTypes(): Iterable
    {
        yield from Type::float();
        yield from Type::double();
        yield from Type::uint();
        yield from Type::uint64();
        yield from Type::int();
        yield ['d37fffffffffffffff', PHP_INT_MAX, /* 9223372036854775807 */];
    }

    /**
     * @test
     * @dataProvider MessagePack\Tests\Data\Type::ext
     */
    public function it_valid_decoded_ext($data, $expected): void
    {
        self::assertEquals($expected, $this->decode(\hex2bin($data)));
    }

    /** @test */
    public function it_throws_when_decode_unknown_byte(): void
    {
        self::expectException(DecodingFailed::class);
        self::expectExceptionCode(DecodingFailed::UNKNOWN_BYTE_HEADER);

        $this->decode("\xc1");
    }

    /**
     * @test
     * @dataProvider insufficientData
     */
    public function it_throws_when_data_is_insufficient($data, $actualLength, $expectedLength): void
    {
        try {
            $this->decode($data);
        } catch (DecodingFailed $e) {
            self::assertSame(DecodingFailed::INSUFFICIENT_DATA, $e->getCode());
            self::assertSame($data, $e->getValue());
            self::assertSame(
                "Not enough data to decode: expected length bytes ${expectedLength}, got ${actualLength}",
                $e->getMessage()
            );
            return;
        }

        self::fail('DecodingFailed was not thrown.');
    }

    public function insufficientData(): Iterable
    {
        yield ''         => ['',     0, 1];
        yield 'str'      => ["\xa1", 0, 1];
        yield 'uint8'    => ["\xcc", 0, 1];
        yield 'uint16'   => ["\xcd", 0, 2];
        yield 'uint32'   => ["\xce", 0, 4];
        yield 'uint64'   => ["\xcf", 0, 8];
        yield 'in8'      => ["\xd0", 0, 1];
        yield 'int16'    => ["\xd1", 0, 2];
        yield 'int32'    => ["\xd2", 0, 4];
        yield 'int64'    => ["\xd3", 0, 8];
        yield 'float32'  => ["\xca", 0, 4];
        yield 'float64'  => ["\xcb", 0, 8];
        yield 'fixext1'  => ["\xd4", 0, 1];
        yield 'fixext2'  => ["\xd5", 0, 2];
        yield 'fixext4'  => ["\xd6", 0, 4];
        yield 'fixext8'  => ["\xd7", 0, 8];
        yield 'fixext16' => ["\xd8", 0, 16];
        yield 'ext8'     => ["\xc7", 0, 1];
        yield 'ext16'    => ["\xc8", 0, 2];
        yield 'ext32'    => ["\xc9", 0, 4];
    }

    private function decode(string $data)
    {
        return (new Decoder)->decode($data);
    }
}
