<?php
declare(strict_types=1);

namespace MessagePack\Exception;

use MessagePack\MessagePackException;
use RuntimeException;

final class EncodingFailed extends RuntimeException implements MessagePackException
{
    /** @var mixed */
    private $value;

    public static function unsupportedType($value): self
    {
        $type = \gettype($value);
        $spec = $value;

        if (\is_object($spec)) {
            $spec = \get_class($spec);
        }

        return new self($value, "Unsupported type: ${type} (${spec})");
    }

    public function getValue()
    {
        return $this->value;
    }

    private function __construct($value, string $message)
    {
        parent::__construct($message);

        $this->value = $value;
    }
}
