<?php
declare(strict_types=1);

namespace MessagePack\Tests\Data;

final class Binary
{
    public const CHAR = 'a';
    public const NUM = "\x01";
    private const CHR = "\X0\X1\X2\X3\X4\X5\X6\X7\X8\X9\Xa\Xb\Xc\Xd\Xe\Xf\X10\X11\X12\X13\X14\X15\X16\X17\X18\X19\X1a\X1b\X1c\X1d\X1e\X1f\X20\X21\X22\X23\X24\X25\X26\X27\X28\X29\X2a\X2b\X2c\X2d\X2e\X2f\X30\X31\X32\X33\X34\X35\X36\X37\X38\X39\X3a\X3b\X3c\X3d\X3e\X3f\X40\X41\X42\X43\X44\X45\X46\X47\X48\X49\X4a\X4b\X4c\X4d\X4e\X4f\X50\X51\X52\X53\X54\X55\X56\X57\X58\X59\X5a\X5b\X5c\X5d\X5e\X5f\X60\X61\X62\X63\X64\X65\X66\X67\X68\X69\X6a\X6b\X6c\X6d\X6e\X6f\X70\X71\X72\X73\X74\X75\X76\X77\X78\X79\X7a\X7b\X7c\X7d\X7e\X7f\X80\X81\X82\X83\X84\X85\X86\X87\X88\X89\X8a\X8b\X8c\X8d\X8e\X8f\X90\X91\X92\X93\X94\X95\X96\X97\X98\X99\X9a\X9b\X9c\X9d\X9e\X9f\Xa0\Xa1\Xa2\Xa3\Xa4\Xa5\Xa6\Xa7\Xa8\Xa9\Xaa\Xab\Xac\Xad\Xae\Xaf\Xb0\Xb1\Xb2\Xb3\Xb4\Xb5\Xb6\Xb7\Xb8\Xb9\Xba\Xbb\Xbc\Xbd\Xbe\Xbf\Xc0\Xc1\Xc2\Xc3\Xc4\Xc5\Xc6\Xc7\Xc8\Xc9\Xca\Xcb\Xcc\Xcd\Xce\Xcf\Xd0\Xd1\Xd2\Xd3\Xd4\Xd5\Xd6\Xd7\Xd8\Xd9\Xda\Xdb\Xdc\Xdd\Xde\Xdf\Xe0\Xe1\Xe2\Xe3\Xe4\Xe5\Xe6\Xe7\Xe8\Xe9\Xea\Xeb\Xec\Xed\Xee\Xef\Xf0\Xf1\Xf2\Xf3\Xf4\Xf5\Xf6\Xf7\Xf8\Xf9\Xfa\Xfb\Xfc\Xfd\Xfe\Xff\X00";

    public static function str(int $len): string
    {
        $byte = self::byteStr($len);
        $str = \str_repeat(self::CHAR, $len);

        return \bin2hex("${byte}${str}");
    }

    public static function arr(int $len): string
    {
        $byte = self::byteArr($len);
        $str = \str_repeat(self::NUM, $len);

        return \bin2hex("${byte}${str}");
    }

    public static function map(int $size): string
    {
        $data = self::byteMap($size);

        for ($i = 1; $i <= $size; ++$i) {
            $x = self::byteInt($i);
            $data .= "${x}${x}";
        }

        return \bin2hex($data);
    }

    private static function byteInt(int $num): string
    {
        // positive fixint
        if ($num <= 0x7f) {
            return self::CHR[$num];
        }
        // uint 8
        if ($num <= 0xff) {
            $b1 = self::CHR[$num];
            return "\xcc${b1}";
        }
        // uint 16
        if ($num <= 0xffff) {
            $b1 = self::CHR[$num >> 8];
            $b2 = self::CHR[$num & 0xff];
            return "\xcd${b1}${b2}";
        }
        // uint 32
        $b1 = self::CHR[$num >> 24];
        $b2 = self::CHR[$num >> 16 & 0xff];
        $b3 = self::CHR[$num >> 8 & 0xff];
        $b4 = self::CHR[$num & 0xff];
        return "\xce${b1}${b2}${b3}${b4}";
    }

    private static function byteStr(int $len): string
    {
        // fixstr
        if ($len < 0x20) {
            return self::CHR[0xa0 | $len];
        }
        // str 8
        if ($len <= 0xff) {
            $b1 = self::CHR[$len];
            return "\xd9${b1}";
        }
        // str 16
        if ($len <= 0xffff) {
            $b1 = self::CHR[$len >> 8];
            $b2 = self::CHR[$len & 0xff];
            return "\xda${b1}${b2}";
        }
        // str 32
        $b1 = self::CHR[$len >> 24];
        $b2 = self::CHR[$len >> 16];
        $b3 = self::CHR[$len >> 8];
        $b4 = self::CHR[$len & 0xff];
        return "\xdb${b1}${b2}${b3}${b4}";
    }

    private static function byteArr(int $size): string
    {
        // fixarray
        if ($size <= 0xf) {
            return self::CHR[0x90 | $size];
        }
        // array 16
        if ($size <= 0xffff) {
            $b1 = self::CHR[$size >> 8];
            $b2 = self::CHR[$size & 0xff];
            return "\xdc${b1}${b2}";
        }
        // array 32
        $b1 = self::CHR[$size >> 24];
        $b2 = self::CHR[$size >> 16];
        $b3 = self::CHR[$size >> 8];
        $b4 = self::CHR[$size & 0xff];
        return "\xdd${b1}${b2}${b3}${b4}";
    }

    private static function byteMap(int $size): string
    {
        // fixmap
        if ($size <= 0xf) {
            return self::CHR[0x80 | $size];
        }
        // map 16
        if ($size <= 0xffff) {
            $b1 = chr($size >> 8);
            $b2 = chr($size & 0xff);
            return "\xde${b1}${b2}";
        }
        // map 32
        $b1 = self::CHR[$size >> 24];
        $b2 = self::CHR[$size >> 16];
        $b3 = self::CHR[$size >> 8];
        $b4 = self::CHR[$size & 0xff];
        return "\xdf${b1}${b2}${b3}${b4}";
    }
}
