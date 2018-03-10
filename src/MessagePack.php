<?php
declare(strict_types=1);

namespace MessagePack;

use MessagePack\{Decoder, Encoder};

final class MessagePack
{
    public static function encode($value, int $mode = null): string
    {
        return (new Encoder($mode))->encode($value);
    }

    public static function decode(string $data)
    {
        return (new Decoder)->decode($data);
    }
}
