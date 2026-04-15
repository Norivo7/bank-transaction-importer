<?php

declare(strict_types=1);

namespace App\Services\Parsers;

use RuntimeException;

final class CsvTransactionParser extends AbstractTransactionParser
{
    public function parse(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Cannot open CSV file.');
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);

            throw new RuntimeException('CSV file is empty or invalid.');
        }

        $normalizedHeaders = $this->normalizeHeaders($headers);
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if ($this->isEmptyRow($data)) {
                continue;
            }

            if (count($data) !== count($normalizedHeaders)) {
                fclose($handle);

                throw new RuntimeException('CSV row does not match header column count.');
            }

            $combinedRow = array_combine($normalizedHeaders, $data);

            $rows[] = $this->normalizeRecord($combinedRow);
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param array<int, mixed> $headers
     * @return array<int, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalizedHeaders = [];

        foreach ($headers as $header) {
            if (!is_string($header)) {
                throw new RuntimeException('CSV header contains invalid value.');
            }

            $normalizedHeaders[] = trim($header);
        }

        return $normalizedHeaders;
    }

    /**
     * @param array<int, mixed> $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value === null) {
                continue;
            }

            if (is_string($value)) {
                if (trim($value) !== '') {
                    return false;
                }

                continue;
            }

            return false;

        }

        return true;
    }
}
