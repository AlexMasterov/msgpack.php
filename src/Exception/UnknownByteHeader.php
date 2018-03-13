<?php
declare(strict_types=1);

namespace MessagePack\Exception;

use MessagePack\{
    Exception\CanGetValue,
    MessagePackException
};
use RuntimeException;
use function dechex;

final class UnknownByteHeader extends RuntimeException implements MessagePackException
{
    use CanGetValue;

    public static function fromOffset(int $value, int $offset): self
    {
        $byte = dechex($value);
        $message = "Can't decode data with byte-header 0x${byte} in position ${offset}";

        return new self($value, $message);
    }
}
