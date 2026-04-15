<?php

declare(strict_types=1);

namespace App\Services\Parsers;

use App\Contracts\TransactionParserInterface;
use RuntimeException;

final class TransactionParserResolver
{
    /**
     * @var array<string, TransactionParserInterface>
     */
    private array $parsers;

    public function __construct(
        CsvTransactionParser $csvTransactionParser,
        JsonTransactionParser $jsonTransactionParser,
        XmlTransactionParser $xmlTransactionParser,
    ) {
        $this->parsers = [
            'csv' => $csvTransactionParser,
            'json' => $jsonTransactionParser,
            'xml' => $xmlTransactionParser,
        ];
    }

    public function resolve(string $extension): TransactionParserInterface
    {
        $normalizedExtension = mb_strtolower(trim($extension));

        if (!array_key_exists($normalizedExtension, $this->parsers)) {
            throw new RuntimeException('Unsupported file format.');
        }

        return $this->parsers[$normalizedExtension];
    }
}
