<?php
declare(strict_types=1);

namespace MessagePack;

use MessagePack\{
    Exception\InsufficientData,
    Exception\UnknownByteHeader,
    Ext
};
use const MessagePack\ORD;
use function MessagePack\{toDouble, toFloat};
use function sprintf;
use function substr;

final class Decoder
{
    /** @var string */
    private $data = '';

    /** @var int */
    private $offset = 0;

    public function decode(string $data)
    {
        $this->data = $data;
        $this->offset = 0;

        return $this->parse();
    }

    private function parse()
    {
        if (!isset($this->data[$this->offset])) {
            throw InsufficientData::fromOffset($this->data, $this->offset, 1);
        }

        $byte = ORD[$this->data[$this->offset++]];

        if ($byte < 0xc0) {
            // positive fixint
            if ($byte < 0x80) {
                return $byte;
            }
            // fixmap
            if ($byte < 0x90) {
                return $this->decodeMap($byte & 0xf);
            }
            // fixarray
            if ($byte < 0xa0) {
                return $this->decodeArray($byte & 0x0f);
            }
            // fixstr
            return $this->decodeStr($byte & 0x1f);
        }
        // negative fixint
        if ($byte > 0xdf) {
            return $byte - 0x100;
        }

        switch ($byte) {
            case 0xc0: return null;
            case 0xc2: return false;
            case 0xc3: return true;

            // bin 8/16/32
            case 0xc4: return $this->decodeStr($this->decodeUint8());
            case 0xc5: return $this->decodeStr($this->decodeUint16());
            case 0xc6: return $this->decodeStr($this->decodeUint32());

            // float 32/64
            case 0xca: return $this->decodeFloat32();
            case 0xcb: return $this->decodeFloat64();

            // uint 8/16/32/64
            case 0xcc: return $this->decodeUint8();
            case 0xcd: return $this->decodeUint16();
            case 0xce: return $this->decodeUint32();
            case 0xcf: return $this->decodeUint64();

            // int 8/16/32/64
            case 0xd0: return $this->decodeInt8();
            case 0xd1: return $this->decodeInt16();
            case 0xd2: return $this->decodeInt32();
            case 0xd3: return $this->decodeInt64();

            // str 8/16/32
            case 0xd9: return $this->decodeStr($this->decodeUint8());
            case 0xda: return $this->decodeStr($this->decodeUint16());
            case 0xdb: return $this->decodeStr($this->decodeUint32());

            // array 16/32
            case 0xdc: return $this->decodeArray($this->decodeUint16());
            case 0xdd: return $this->decodeArray($this->decodeUint32());

            // map 16/32
            case 0xde: return $this->decodeMap($this->decodeUint16());
            case 0xdf: return $this->decodeMap($this->decodeUint32());

            // fixext 1/2/4/8/16
            case 0xd4: return $this->decodeExt(1);
            case 0xd5: return $this->decodeExt(2);
            case 0xd6: return $this->decodeExt(4);
            case 0xd7: return $this->decodeExt(8);
            case 0xd8: return $this->decodeExt(16);

            // ext 8/16/32
            case 0xc7: return $this->decodeExt($this->decodeUint8());
            case 0xc8: return $this->decodeExt($this->decodeUint16());
            case 0xc9: return $this->decodeExt($this->decodeUint32());
          }

        throw UnknownByteHeader::fromOffset($byte, $this->offset);
    }

    private function decodeFloat32(): float
    {
        if (!isset($this->data[$this->offset + 3])) {
            throw InsufficientData::fromOffset($this->data, $this->offset, 4);
        }

        $num = ORD[$this->data[$this->offset++]] * 0x1000000
            | ORD[$this->data[$this->offset++]] << 16
            | ORD[$this->data[$this->offset++]] << 8
            | ORD[$this->data[$this->offset++]];

        return toFloat($num);
    }

    private function decodeFloat64(): float
    {
        if (!isset($this->data[$this->offset + 7])) {
            throw InsufficientData::fromOffset($this->data, $this->offset, 8);
        }

        $x = ORD[$this->data[$this->offset++]] * 0x1000000
            | ORD[$this->data[$this->offset++]] << 16
            | ORD[$this->data[$this->offset++]] << 8
            | ORD[$this->data[$this->offset++]];

        $y = ORD[$this->data[$this->offset++]] * 0x1000000
            | ORD[$this->data[$this->offset++]] << 16
            | ORD[$this->data[$this->offset++]] << 8
            | ORD[$this->data[$this->offset++]];

        return toDouble($y, $x);
    }

    private function decodeUint8(): int
    {
        if (!isset($this->data[$this->offset])) {
            throw InsufficientData::fromOffset($this->data, $this->offset, 1);
        }

        return ORD($this->data[$this->offset++]);
    }

    private function decodeUint16(): int
    {
        if (!isset($this->data[$this->offset + 1])) {
            throw InsufficientData::fromOffset($this->data, $this->offset, 2);
        }

        return ORD[$this->data[$this->offset++]] << 8
            | ORD[$this->data[$this->offset++]];
    }

    private function decodeUint32(): int
    {
        if (!isset($this->data[$this->offset + 3])) {
            throw InsufficientData::fromOffset($this->data, $this->offset, 4);
        }

        return ORD[$this->data[$this->offset++]] * 0x1000000
            | ORD[$this->data[$this->offset++]] << 16
            | ORD[$this->data[$this->offset++]] << 8
            | ORD[$this->data[$this->offset++]];
    }

    private function decodeUint64()
    {
        if (!isset($this->data[$this->offset + 7])) {
            throw InsufficientData::fromOffset($this->data, $this->offset, 8);
        }

        $num = (ORD[$this->data[$this->offset++]] * 0x1000000
            | ORD[$this->data[$this->offset++]] << 16
            | ORD[$this->data[$this->offset++]] << 8
            | ORD[$this->data[$this->offset++]]) * 0x100000000
            | ORD[$this->data[$this->offset++]] * 0x1000000
            | ORD[$this->data[$this->offset++]] << 16
            | ORD[$this->data[$this->offset++]] << 8
            | ORD[$this->data[$this->offset++]];

        return $num < 0 ? sprintf('%u', $num) : $num;
    }

    private function decodeInt8(): int
    {
        if (!isset($this->data[$this->offset])) {
            throw InsufficientData::fromOffset($this->data, $this->offset, 1);
        }

        $num = ORD[$this->data[$this->offset++]];

        return $num & 0x80 ? $num - 0x100 : $num;
    }

    private function decodeInt16(): int
    {
        if (!isset($this->data[$this->offset + 1])) {
            throw InsufficientData::fromOffset($this->data, $this->offset, 2);
        }

        $num = ORD[$this->data[$this->offset++]] << 8
            | ORD[$this->data[$this->offset++]];

        return $num & 0x8000 ? $num - 0x10000 : $num;
    }

    private function decodeInt32(): int
    {
        if (!isset($this->data[$this->offset + 3])) {
            throw InsufficientData::fromOffset($this->data, $this->offset, 4);
        }

        $num = ORD[$this->data[$this->offset++]] << 24
            | ORD[$this->data[$this->offset++]] << 16
            | ORD[$this->data[$this->offset++]] << 8
            | ORD[$this->data[$this->offset++]];

        return $num & 0x80000000 ? $num - 0x100000000 : $num;
    }

    private function decodeInt64(): int
    {
        if (!isset($this->data[$this->offset + 7])) {
            throw InsufficientData::fromOffset($this->data, $this->offset, 8);
        }

        $num = ORD[$this->data[$this->offset++]] << 24
            | ORD[$this->data[$this->offset++]] << 16
            | ORD[$this->data[$this->offset++]] << 8
            | ORD[$this->data[$this->offset++]];

        if ($num & 0x80000000) {
            $num -= 0x100000000;
        }

        return $num * 0x100000000
            | ORD[$this->data[$this->offset++]] * 0x1000000
            | ORD[$this->data[$this->offset++]] << 16
            | ORD[$this->data[$this->offset++]] << 8
            | ORD[$this->data[$this->offset++]];
    }

    private function decodeStr(int $length): string
    {
        if (0 === $length) {
            return '';
        }

        if (!isset($this->data[$this->offset + $length - 1])) {
            throw InsufficientData::fromOffset($this->data, $this->offset, $length);
        }

        $str = substr($this->data, $this->offset++, $length);
        $this->offset += $length;

        return $str;
    }

    private function decodeArray(int $size): array
    {
        $array = [];
        while ($size--) {
            $array[] = $this->parse();
        }

        return $array;
    }

    private function decodeMap(int $size): array
    {
        $map = [];
        while ($size--) {
            $map[$this->parse()] = $this->parse();
        }

        return $map;
    }

    private function decodeExt(int $length): Ext
    {
        if (!isset($this->data[$this->offset + $length - 1])) {
            throw InsufficientData::fromOffset($this->data, $this->offset, $length);
        }

        $type = ORD[$this->data[$this->offset++]];
        $data = substr($this->data, $this->offset++, $length);

        $this->offset += $length;

        return Ext::make($type, $data);
    }
}
