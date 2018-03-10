<?php
declare(strict_types=1);

namespace MessagePack\Tests\Data;

use MessagePack\Tests\Data\Binary as b;
use PHPUnit\Framework\TestCase;

final class Type
{
    public static function nil(): Iterable
    {
        yield ['c0', null];
    }

    public static function bool(): Iterable
    {
        yield ['c2', false];
        yield ['c3', true];
    }

    public static function uint(): Iterable
    {
        // 5 (positive fixint)
        yield ['00', 0];
        yield ['7f', 127];
        // 8
        yield ['cc80', 128];
        yield ['ccff', 255];
        // 16
        yield ['cd0100', 256];
        yield ['cdffff', 65535];
        // 32
        yield ['ce00010000', 65536];
        yield ['ceffffffff', 4294967295];
    }

    public static function uint64(): Iterable
    {
        yield ['cf0000000100000000', 4294967296];
        yield ['cf7fffffffffffffff', PHP_INT_MAX /* 9223372036854775807 */];
    }

    public static function int(): Iterable
    {
        // 5 (negative fixint)
        yield ['ff', -1];
        yield ['e0', -32];
        // 8
        yield ['d0df', -33];
        yield ['d080', -128];
        // 16
        yield ['d1ff7f', -129];
        yield ['d18000', -32768];
        // 32
        yield ['d2ffff7fff', -32769];
        yield ['d280000000', -2147483648];
        // 64
        yield ['d3ffffffff7fffffff', -2147483649];
        yield ['d38000000000000000', PHP_INT_MIN /* -9223372036854775808 */];
    }

    public static function float(): Iterable
    {
        yield ['ca00000000', 0.0];
        yield ['ca40200000', 2.5];
        yield ['ca000080ff', INF];
        yield ['caffffffff', NAN];
    }

    public static function double(): Iterable
    {
        yield ['cb0000000000000000', 0.0];
        yield ['cb4004000000000000', 2.5];
        yield ['cb0010000000000000', PHP_FLOAT_MIN /* 2.2250738585072E-308 */];
        yield ['cb7fefffffffffffff', PHP_FLOAT_MAX /* 1.7976931348623E+308 */];
        // yield ['cbfff8000000000000', NAN];
        yield ['cb7ff0000000000000', INF];
        yield ['cbfff0000000000000', -INF];
    }

    public static function string(): Iterable
    {
        // fixstr a0 - bf
        yield [b::str(0), ''];
        yield [b::str(31), str(31)];
        // 8 d9
        yield [b::str(32), str(32)];
        yield [b::str(255), str(255)];
        // 16 da
        yield [b::str(256), str(256)];
        yield [b::str(65535), str(65535)];
        // 32 db
        yield [b::str(65536), str(65536)];
    }

    public static function array(): Iterable
    {
        // fixarray 90 - 9f
        yield [b::arr(0), arr(0)];
        yield [b::arr(15), arr(15)];
        // 16 dc
        yield [b::arr(16), arr(16)];
        yield [b::arr(65535), arr(65535)];
        // 32 dd
        yield [b::arr(65536), arr(65536)];
    }

    public static function map(): Iterable
    {
        // fixmap 80 - 8f
        yield [b::map(1), map(1)];
        yield [b::map(15), map(15)];
        // 16 de
        yield [b::map(16), map(16)];
        yield [b::map(65535), map(65535)];
        // 32 df
        yield [b::map(65536), map(65536)];
    }
}

function str(int $len): string
{
    return \str_repeat('a', $len);
}

function arr(int $size): array
{
    return \array_fill(0, $size, 1);
}

function map(int $size): array
{
    $map = [];
    for ($i = 1; $i <= $size; ++$i) {
        $map[$i] = $i;
    }
    return $map;
}
