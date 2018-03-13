<?php
declare(strict_types=1);

namespace MessagePack\Exception;

use MessagePack\{
    Exception\CanGetValue,
    MessagePackException
};
use RuntimeException;
use function strlen;

final class InsufficientData extends RuntimeException implements MessagePackException
{
    use CanGetValue;

    public static function fromOffset(string $data, int $offset, int $expectedLength): self
    {
        $actualLength = strlen($data) - $offset;
        $message = "Not enough data to decode: expected length ${expectedLength}, got ${actualLength}";

        return new self($data, $message);
    }
}
