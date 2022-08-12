<?php

declare(strict_types=1);

class Hash
{
    private string $hash;
    const HASH_ALGORITHM = "sha256";

    function __construct(string $input)
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
            throw new \InvalidRequestException("invalid hash");
        }
        $this->hash = $hash;
    }

    function __toString()
    {
        return $this->get();
    }
}
