<?php
declare(strict_types=1);

namespace MessagePack\Exception;

use MessagePack\MessagePackException;
use RuntimeException;

final class DecodingFailed extends RuntimeException implements MessagePackException
{
    /** @const int*/
    public const UNKNOWN_BYTE_HEADER = 0;
    public const INSUFFICIENT_DATA = 1;

    /** @var mixed */
    private $value;

    public static function unknownByteHeader(int $value, int $offset): self
    {
        $byte = \dechex($value);

        return new self(
            $value,
            "Can't decode data with byte-header 0x${byte} in position ${offset}",
            self::UNKNOWN_BYTE_HEADER
        );
    }

    public static function insufficientData(string $buffer, int $offset, int $expectedLength): self
    {
        $actualLength = \strlen($buffer) - $offset;

        return new self(
            $buffer,
            "Not enough data to decode: expected length bytes ${expectedLength}, got ${actualLength}",
            self::INSUFFICIENT_DATA
        );
    }

    public function getValue()
    {
        return $this->value;
    }

    private function __construct($value, string $message, int $code = null)
    {
        parent::__construct($message, $code);

        $this->value = $value;
    }
}
