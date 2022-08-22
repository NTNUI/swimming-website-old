<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Util;

class Hash
{
    private string $hash;
    public const HASH_ALGORITHM = "sha256";

    public function __construct(string $input)
    {
        $this->hash = hash(self::HASH_ALGORITHM, $input);
    }

    public function get(): string
    {
        return $this->hash;
    }

    public function set(string $hash): void
    {
        if (strlen($hash) !== 64) {
            throw new \InvalidArgumentException("invalid hash");
        }
        $this->hash = $hash;
    }

    public function __toString()
    {
        return $this->get();
    }
}
