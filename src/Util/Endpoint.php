<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Util;

interface Endpoint
{
    public static function run(string $requestMethod, array $args, array $request): Response;
}
