<?php

declare(strict_types=1);

namespace App\Services\Validation;

use Closure;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

final class TransactionRecordValidator
{
    /**
     * @param array<string, string|null> $record
     * @return array{is_valid: bool, errors: array<int, string>}
     */
    public function validate(array $record): array
    {
        $normalizedRecord = $this->normalizeRecord($record);

        $validator = Validator::make($normalizedRecord, [
            'transaction_id' => ['required', 'uuid'],
            'account_number' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (!is_string($value)) {
                        $fail('The account number must be a valid IBAN.');

                        return;
                    }

                    if (!$this->isValidIban($value)) {
                        $fail('The account number must be a valid IBAN.');
                    }
                },
            ],
            'transaction_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['required', 'regex:/^[A-Z]{3}$/'],
        ]);

        if ($validator->fails()) {
            /** @var array<int, string> $errors */
            $errors = $validator->errors()->all();

            return [
                'is_valid' => false,
                'errors' => $errors,
            ];
        }

        return [
            'is_valid' => true,
            'errors' => [],
        ];
    }

    /**
     * @param array<string, string|null> $record
     * @return array<string, string|null>
     */
    public function normalizeRecord(array $record): array
    {
        $transactionId = $this->normalizeNullableString($record['transaction_id'] ?? null);
        $accountNumber = $this->normalizeAccountNumber($record['account_number'] ?? null);
        $transactionDate = $this->normalizeNullableString($record['transaction_date'] ?? null);
        $amount = $this->normalizeNullableString($record['amount'] ?? null);
        $currency = $this->normalizeCurrency($record['currency'] ?? null);

        return [
            'transaction_id' => $transactionId,
            'account_number' => $accountNumber,
            'transaction_date' => $transactionDate,
            'amount' => $amount,
            'currency' => $currency,
        ];
    }

    private function normalizeNullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmedValue = trim($value);

        if ($trimmedValue === '') {
            return null;
        }

        return $trimmedValue;
    }

    private function normalizeAccountNumber(?string $accountNumber): ?string
    {
        $normalizedAccountNumber = $this->normalizeNullableString($accountNumber);

        if ($normalizedAccountNumber === null) {
            return null;
        }

        return str_replace(' ', '', $normalizedAccountNumber);
    }

    private function normalizeCurrency(?string $currency): ?string
    {
        $normalizedCurrency = $this->normalizeNullableString($currency);

        if ($normalizedCurrency === null) {
            return null;
        }

        return mb_strtoupper($normalizedCurrency);
    }

    private function isValidIban(string $iban): bool
    {
        $normalizedIban = mb_strtoupper(str_replace(' ', '', trim($iban)));

        if (strlen($normalizedIban) < 15 || strlen($normalizedIban) > 34) {
            return false;
        }

        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $normalizedIban)) {
            return false;
        }

        $rearrangedIban = substr($normalizedIban, 4) . substr($normalizedIban, 0, 4);
        $numericRepresentation = $this->convertIbanToNumericRepresentation($rearrangedIban);

        if ($numericRepresentation === null) {
            return false;
        }

        return $this->calculateMod97($numericRepresentation) === 1;
    }

    private function convertIbanToNumericRepresentation(string $value): ?string
    {
        $numericRepresentation = '';

        foreach (str_split($value) as $character) {
            if (ctype_alpha($character)) {
                $numericRepresentation .= $this->convertLetterToDigits($character);

                continue;
            }

            if (ctype_digit($character)) {
                $numericRepresentation .= $character;

                continue;
            }

            return null;
        }

        return $numericRepresentation;
    }

    private function convertLetterToDigits(string $character): string
    {
        return match ($character) {
            'A' => '10',
            'B' => '11',
            'C' => '12',
            'D' => '13',
            'E' => '14',
            'F' => '15',
            'G' => '16',
            'H' => '17',
            'I' => '18',
            'J' => '19',
            'K' => '20',
            'L' => '21',
            'M' => '22',
            'N' => '23',
            'O' => '24',
            'P' => '25',
            'Q' => '26',
            'R' => '27',
            'S' => '28',
            'T' => '29',
            'U' => '30',
            'V' => '31',
            'W' => '32',
            'X' => '33',
            'Y' => '34',
            'Z' => '35',
            default => throw new RuntimeException('Invalid IBAN character provided.'),
        };
    }

    private function calculateMod97(string $numericRepresentation): int
    {
        $remainder = 0;

        foreach (str_split($numericRepresentation) as $digit) {
            $remainder = ($remainder * 10 + $this->toDigit($digit)) % 97;
        }

        return $remainder;
    }

    private function toDigit(string $digit): int
    {
        return match ($digit) {
            '0' => 0,
            '1' => 1,
            '2' => 2,
            '3' => 3,
            '4' => 4,
            '5' => 5,
            '6' => 6,
            '7' => 7,
            '8' => 8,
            '9' => 9,
            default => throw new RuntimeException('Invalid digit provided.'),
        };
    }
}
