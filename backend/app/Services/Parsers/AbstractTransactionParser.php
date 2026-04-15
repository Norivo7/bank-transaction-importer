<?php

declare(strict_types=1);

namespace App\Services\Parsers;

use App\Contracts\TransactionParserInterface;

abstract class AbstractTransactionParser implements TransactionParserInterface
{
    /**
     * @param array<string, mixed> $record
     * @return array<string, string|null>
     */
    protected function normalizeRecord(array $record): array
    {
        return [
            'transaction_id' => $this->normalizeValue($record['transaction_id'] ?? null),
            'account_number' => $this->normalizeValue($record['account_number'] ?? null),
            'transaction_date' => $this->normalizeValue($record['transaction_date'] ?? null),
            'amount' => $this->normalizeValue($record['amount'] ?? null),
            'currency' => $this->normalizeValue($record['currency'] ?? null),
        ];
    }

    protected function normalizeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $this->normalizeString($value);
        }

        if (is_int($value)) {
            return (string)$value;
        }

        if (is_float($value)) {
            return $this->normalizeFloat($value);
        }

        return null;
    }

    protected function normalizeString(string $value): ?string
    {
        $trimmedValue = trim($value);

        if ($trimmedValue === '') {
            return null;
        }

        return $trimmedValue;
    }

    protected function normalizeFloat(float $value): string
    {
        $normalizedValue = rtrim(rtrim(sprintf('%.14F', $value), '0'), '.');

        if ($normalizedValue === '-0') {
            return '0';
        }

        return $normalizedValue;
    }
}
