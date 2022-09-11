<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Interface;

use NTNUI\Swimming\Util\Response;

interface Endpoint
{
    public static function run(string $requestMethod, array $args, array $request): Response;
}
