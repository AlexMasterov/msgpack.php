<?php
declare(strict_types=1);

namespace MessagePack;

use MessagePack\{
    Exception\UnsupportedType,
    Ext
};
use const MessagePack\CHR;
use function array_values;
use function count;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function pack;
use function strlen;

final class Encoder
{
    public const FORCE_AUTO = 0b00;
    public const FORCE_ARR = 0b01;
    public const FORCE_MAP = 0b10;

    /** @var int */
    private $typeDetectionMode = self::FORCE_AUTO;

    public function __construct(int $typeDetectionMode = null)
    {
        if (null !== $typeDetectionMode) {
            $this->typeDetectionMode |= $typeDetectionMode;
        }
    }

    public function encode($value): string
    {
        if (is_int($value)) {
            return $this->encodeInt($value);
        }
        if (is_string($value)) {
            return $this->encodeStr($value);
        }
        if (is_array($value)) {
            if ($this->typeDetectionMode ^ self::FORCE_AUTO) {
                return $this->typeDetectionMode & self::FORCE_MAP
                    ? $this->encodeMap($value)
                    : $this->encodeArray($value);
            }
            return array_values($value) === $value
                ? $this->encodeArray($value)
                : $this->encodeMap($value);
        }
        if (is_float($value)) {
            return $this->encodeFloat($value);
        }
        if (is_bool($value)) {
            return $this->encodeBool($value);
        }
        if (null === $value) {
            return $this->encodeNil();
        }
        if ($value instanceof Ext) {
            return $this->encodeExt($value);
        }

        throw UnsupportedType::withValue($value);
    }

    public function encodeNil(): string
    {
        return "\xc0";
    }

    public function encodeBool(bool $value): string
    {
        return $value ? "\xc3" : "\xc2";
    }

    public function encodeFloat(float $num): string
    {
        $b1 = pack('E', $num);

        return "\xcb${b1}";
    }

    public function encodeInt(int $num): string
    {
        if ($num >= 0) {
            // positive fixint
            if ($num <= 0x7f) {
                return CHR[$num];
            }
            // uint 8
            if ($num <= 0xff) {
                $b1 = CHR[$num];
                return "\xcc${b1}";
            }
            // uint 16
            if ($num <= 0xffff) {
                $b1 = CHR[$num >> 8];
                $b2 = CHR[$num & 0xff];
                return "\xcd${b1}${b2}";
            }
            // uint 32
            if ($num <= 0xffffffff) {
                $b1 = CHR[$num >> 24];
                $b2 = CHR[$num >> 16 & 0xff];
                $b3 = CHR[$num >> 8 & 0xff];
                $b4 = CHR[$num & 0xff];
                return "\xce${b1}${b2}${b3}${b4}";
            }
            // uint 64
            return self::encodeUint64("\xcf", $num);
        }
        // negative fixint
        if ($num >= -0x20) {
            return CHR[$num & 0xff];
        }
        // int 8
        if ($num >= -0x80) {
            $b1 = CHR[$num & 0xff];
            return "\xd0${b1}";
        }
        // int 16
        if ($num >= -0x8000) {
            $b1 = CHR[$num >> 8 & 0xff];
            $b2 = CHR[$num & 0xff];
            return "\xd1${b1}${b2}";
        }
        // int 32
        if ($num >= -0x80000000) {
            $num |= 0x100000000;
            $b1 = CHR[$num >> 24 & 0xff];
            $b2 = CHR[$num >> 16 & 0xff];
            $b3 = CHR[$num >> 8 & 0xff];
            $b4 = CHR[$num & 0xff];
            return "\xd2${b1}${b2}${b3}${b4}";
        }
        // int 64
        return self::encodeUint64("\xd3", $num);
    }

    public function encodeStr(string $str): string
    {
        $len = strlen($str);
        // fixstr
        if ($len < 0x20) {
            $b0 = CHR[0xa0 | $len];
            return "${b0}${str}";
        }
        // str 8
        if ($len <= 0xff) {
            $b1 = CHR[$len];
            return "\xd9${b1}${str}";
        }
        // str 16
        if ($len <= 0xffff) {
            $b1 = CHR[$len >> 8];
            $b2 = CHR[$len & 0xff];
            return "\xda${b1}${b2}${str}";
        }
        // str 32
        $b1 = CHR[$len >> 24];
        $b2 = CHR[$len >> 16];
        $b3 = CHR[$len >> 8];
        $b4 = CHR[$len & 0xff];
        return "\xdb${b1}${b2}${b3}${b4}${str}";
    }

    public function encodeArray(array $array): string
    {
        $size = count($array);
        $data = self::encodeArrayHeader($size);

        foreach ($array as $value) {
            $data .= $this->encode($value);
        }

        return $data;
    }

    public function encodeMap(array $map): string
    {
        $size = count($map);
        $data = self::encodeMapHeader($size);

        foreach ($map as $key => $value) {
            $data .= "{$this->encode($key)}{$this->encode($value)}";
        }

        return $data;
    }

    public function encodeExt(Ext $ext): string
    {
        $type = CHR[$ext->type()];
        $data = $ext->data();

        $len = strlen($data);

        // fixext 1/2/4/8/16
        switch ($len) {
            case 1: return "\xd4${type}${data}";
            case 2: return "\xd5${type}${data}";
            case 4: return "\xd6${type}${data}";
            case 8: return "\xd7${type}${data}";
            case 16: return "\xd8${type}${data}";
        }
        // ext 8
        if ($len <= 0xff) {
            $b1 = CHR[$len];
            return "\xc7${b1}${type}${data}";
        }
        // ext 16
        if ($len <= 0xffff) {
            $b2 = CHR[$len & 0xff];
            $b1 = CHR[$len >> 8];
            return "\xc8${b1}${b2}${type}${data}";
        }
        // ext 32
        $b1 = CHR[$len >> 24 & 0xff];
        $b2 = CHR[$len >> 16 & 0xff];
        $b3 = CHR[$len >> 8 & 0xff];
        $b4 = CHR[$len & 0xff];
        return "\xc9${b1}${b2}${b3}${b4}${type}${data}";
    }

    private static function encodeArrayHeader(int $size): string
    {
        // fixarray
        if ($size <= 0xf) {
            return CHR[0x90 | $size];
        }
        // array 16
        if ($size <= 0xffff) {
            $b1 = CHR[$size >> 8];
            $b2 = CHR[$size & 0xff];
            return "\xdc${b1}${b2}";
        }
        // array 32
        $b1 = CHR[$size >> 24];
        $b2 = CHR[$size >> 16];
        $b3 = CHR[$size >> 8];
        $b4 = CHR[$size & 0xff];
        return "\xdd${b1}${b2}${b3}${b4}";
    }

    private static function encodeMapHeader(int $size): string
    {
        // fixmap
        if ($size <= 0xf) {
            return CHR[0x80 | $size];
        }
        // map 16
        if ($size <= 0xffff) {
            $b1 = CHR[$size >> 8];
            $b2 = CHR[$size & 0xff];
            return "\xde${b1}${b2}";
        }
        // map 32
        $b1 = CHR[$size >> 24];
        $b2 = CHR[$size >> 16];
        $b3 = CHR[$size >> 8];
        $b4 = CHR[$size & 0xff];
        return "\xdf${b1}${b2}${b3}${b4}";
    }

    private static function encodeUint64(string $byte, int $value): string
    {
        $hi = ($value & 0xffffffff00000000) >> 32;
        $lo = $value & 0x00000000ffffffff;

        $b1 = CHR[$hi >> 24 & 0xff];
        $b2 = CHR[$hi >> 16 & 0xff];
        $b3 = CHR[$hi >> 8 & 0xff];
        $b4 = CHR[$hi & 0xff];

        $b5 = CHR[$lo >> 24 & 0xff];
        $b6 = CHR[$lo >> 16 & 0xff];
        $b7 = CHR[$lo >> 8 & 0xff];
        $b8 = CHR[$lo & 0xff];

        return "${byte}${b1}${b2}${b3}${b4}${b5}${b6}${b7}${b8}";
    }
}
