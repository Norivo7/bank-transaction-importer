<?php

declare(strict_types=1);

namespace App\Services\Parsers;

use RuntimeException;

final class JsonTransactionParser extends AbstractTransactionParser
{
    public function parse(string $path): array
    {
        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException('Cannot read JSON file.');
        }

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid JSON format.');
        }

        $rows = [];

        foreach ($decoded as $record) {
            if (!is_array($record)) {
                throw new RuntimeException('Invalid JSON record structure.');
            }

            $rows[] = $this->normalizeRecord($record);
        }

        return $rows;
    }
}
