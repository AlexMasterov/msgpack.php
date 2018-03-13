<?php
declare(strict_types=1);

namespace MessagePack\Exception;

trait CanGetValue
{
    /** @var mixed */
    private $value;

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
