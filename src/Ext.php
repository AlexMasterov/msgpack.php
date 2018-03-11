<?php
declare(strict_types=1);

namespace MessagePack;

final class Ext
{
    /** @var int */
    private $type;

    /** @var string */
    private $data;

    public static function make(int $type, string $data): self
    {
        $ext = new self();
        $ext->type = $type;
        $ext->data = $data;

        return $ext;
    }

    public function type(): int
    {
        return $this->type;
    }

    public function data(): string
    {
        return $this->data;
    }

    private function __construct()
    {
    }
}
