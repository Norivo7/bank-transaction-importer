<?php

declare(strict_types=1);

namespace App\Contracts;

interface TransactionParserInterface
{
    /**
     * @return array<int, array<string, string|null>>
     */
    public function parse(string $path): array;
}
