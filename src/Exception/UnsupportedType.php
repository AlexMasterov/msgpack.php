<?php
declare(strict_types=1);

namespace MessagePack\Exception;

use MessagePack\{
    Exception\CanGetValue,
    MessagePackException
};
use RuntimeException;
use function get_class;
use function gettype;
use function is_object;

final class UnsupportedType extends RuntimeException implements MessagePackException
{
    use CanGetValue;

    public static function withValue($value): self
    {
        $type = gettype($value);
        $spec = $value;

        if (is_object($spec)) {
            $spec = get_class($spec);
        }

        return new self($value, "Could not encode: ${type} (${spec})");
    }
}
